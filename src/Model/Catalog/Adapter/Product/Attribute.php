<?php

namespace Gudtech\RetailOps\Model\Catalog\Adapter\Product;

use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute as EavAttribute;
use Magento\Eav\Model\AttributeSetRepository;
use Magento\Eav\Model\Entity;
use Magento\Eav\Model\Entity\Attribute\SetFactory as AttributeSetFactory;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Collection as AttributeCollection;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Group\Collection as AttributeGroupCollection;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\Collection as AttributeSetCollection;
use Magento\Eav\Setup\EavSetup;
use Magento\Framework\Exception\InputException;
use Gudtech\RetailOps\Model\Catalog\Adapter;
use Magento\Framework\Phrase;

class Attribute extends Adapter
{
    /**
     * Attributes to skip while unsetting missing attributes
     *
     * @var array
     */
    const SYSTEM_ATTRIBUTES = ['has_options', 'required_options', 'media_gallery'];

    /**
     * Attribute group name to use for adding new attributes to a set.
     *
     * @var string
     */
    const ATTRIBUTE_GROUP_NAME = 'Product Details';

    /**
     * Mapping of attribute values
     *
     * @var array
     */
    const ATTRIBUTE_VALUE_MAP = [
        'visibility' => [
            'Visible' => 'Catalog, Search',
            'Invisible' => 'Not Visible Individually'
        ]
    ];

    /**
     * Holds RetailOps attribute groups to process statically.
     *
     * @var array
     */
    const STATIC_ATTRIBUTE_GROUPS = [
        'General',
        'Meta Information',
        'Prices'
    ];

    /**
     * Mapping of RetailOps attributes to Magento attribute codes.
     *
     * @var array
     */
    const ATTRIBUTE_MAP = [
        'manufacturers_suggested_retail_price' => 'msrp',
        'meta_keywords' => 'meta_keyword'
    ];

    private $simpleAttributes = [];
    private $sourceAttributes = [];
    private $multiSelectAttributes = [];
    private $attributeOptions;
    private $newAttributeOptions = [];

    /**
     * Array of already processed attribute codes to avoid double save.
     *
     * @var array
     */
    protected $processedAttributes = [];

    private $eavEntity;
    private $eavAttribute;
    private $eavSetup;
    private $attributeCollection;
    private $attributeSetCollection;
    private $attributeSetFactory;
    private $attributeSetRepository;
    private $attributeGroupCollection;

    public function __construct(
        Entity $eavEntity,
        EavAttribute $eavAttribute,
        EavSetup $eavSetup,
        AttributeCollection $attributeCollection,
        ProductAttributeRepositoryInterface $attributeRepository,
        AttributeSetCollection $attributeSetCollection,
        AttributeSetFactory $attributeSetFactory,
        AttributeSetRepository $attributeSetRepository,
        AttributeGroupCollection $attributeGroupCollection
    ) {
        $this->eavEntity = $eavEntity;
        $this->eavAttribute = $eavAttribute;
        $this->eavSetup = $eavSetup;
        $this->attributeCollection = $attributeCollection;
        $this->attributeRepository = $attributeRepository;
        $this->attributeSetCollection = $attributeSetCollection;
        $this->attributeSetFactory = $attributeSetFactory;
        $this->attributeSetRepository = $attributeSetRepository;
        $this->attributeGroupCollection = $attributeGroupCollection;

        $this->initAttributes();
    }

    /**
     * @param array $data
     * @return $this
     */
    public function prepareData(array &$productData)
    {
        $this->prepareAttributeSet($productData);
        $this->prepareAttributes($productData);
        $this->prepareAttributeOptions($productData);
        $this->prepareAttributeValues($productData);
        return $this;
    }

    /**
     * @return $this
     */
    public function afterDataPrepare()
    {
        $this->addAttributeOptions($this->newAttributeOptions);

        return $this;
    }

    /**
     * @param array $productData
     * @param Product $product
     * @return mixed|void
     */
    public function processData(array $productData, Product $product)
    {
        $product->setAttributeSetId($this->getAttributeSetIdByName($productData['Additional Attributes']['Attribute Set']));

        $this->processStaticAttributes($productData, $product);
        $this->processAttributes($productData, $product);
        $this->unsetProductAttributes($productData, $product);
    }

    /**
     * @return mixed
     */
    public function getAttributeOptions()
    {
        return $this->attributeOptions;
    }

    /**
     * Returns the entity type id.
     *
     * @return int
     */
    private function getEntityTypeId()
    {
        return $this->eavEntity->setType(Product::ENTITY)->getTypeId();
    }

    /**
     * Returns all attribute sets
     *
     * @return array
     */
    private function getAttributeSets()
    {
        return $this->attributeSetCollection->setEntityTypeFilter($this->getEntityTypeId())->toOptionHash();
    }

    /**
     * @return array
     */
    private function getAttributeSetGroups()
    {
        $groups = [];
        foreach ($this->attributeGroupCollection as $group) {
            if (!isset($groups[$group->getAttributeSetId()])) {
                $groups[$group->getAttributeSetId()] = [];
            }
            $groups[$group->getAttributeSetId()][$group->getAttributeGroupName()] = $group->getId();
        }

        return $groups;
    }

    private function getAttributeSetGroupId($attributeSetId, $groupName)
    {
        $attributeSetGroups = $this->getAttributeSetGroups();

        if (isset($attributeSetGroups[$attributeSetId][$groupName])) {
            return $attributeSetGroups[$attributeSetId][$groupName];
        }
    }

    /**
     * Init product attributes and attributes options
     */
    private function initAttributes()
    {
        $this->attributeCollection->clear();
        $this->attributeCollection->setEntityTypeFilter($this->getEntityTypeId());
        $this->attributeCollection->addFieldToSelect('*');

        foreach ($this->attributeCollection as $attribute) {
            $attributeCode = $attribute->getAttributeCode();
            if ($this->hasSource($attribute)) {
                $this->attributeOptions[$attributeCode] =
                    $this->arrayToOptionHash(
                        $attribute->getSource()->getAllOptions(),
                        'value',
                        'label',
                        false
                    );
                $this->sourceAttributes[$attribute->getId()] = $attributeCode;
                if ($attribute->getFrontendInput() === 'multiselect') {
                    $this->multiSelectAttributes[] = $attributeCode;
                }
            } else {
                $this->simpleAttributes[$attribute->getId()] = $attributeCode;
            }
        }
    }

    /**
     * Returns true of attribute has source model, otherwise false.
     *
     * @param $attribute
     * @return bool
     */
    private function hasSource($attribute)
    {
        return $attribute->getFrontendInput() === 'select' || $attribute->getFrontendInput() === 'multiselect'
            || $attribute->getData('source_model') != '';
    }

    /**
     * @param $data
     * @return mixed
     */
    private function prepareAttributeSet(array $data)
    {
        if (!empty($data['Additional Attributes']['Attribute Set'])) {
            $attributeSet = $data['Additional Attributes']['Attribute Set'];
            $attributeSetId = $this->getAttributeSetIdByName($attributeSet);
            if ($attributeSetId === false) {
                $attributeSetId = $this->createAttributeSet($attributeSet, $data['General']['SKU']);
                $this->attributeSets[$attributeSetId] = $attributeSet;
            }
        } else {
            throw new InputException(new Phrase("Attribute set not provided for SKU: " . $data['General']['SKU']));
        }

        $data['attribute_set_id'] = $attributeSetId;
    }

    /**
     * Creates a new attribute set.
     *
     * @param $name
     * @return int
     */
    private function createAttributeSet($name, $sku)
    {
        $attributeSet = $this->attributeSetFactory->create();
        $attributeSet->setName($name);
        $attributeSet->setEntityTypeId($this->getEntityTypeId());
        $attributeSet->validate();
        $attributeSet->initFromSkeleton($this->eavEntity->getEntityType()->getDefaultAttributeSetId());
        $attributeSet = $this->attributeSetRepository->save($attributeSet);

        return $attributeSet->getId();
    }

    /**
     * @param $set
     * @return mixed
     */
    protected function getAttributeSetIdByName($set)
    {
        $tolower = function_exists('mb_strtolower') ? 'mb_strtolower' : 'strtolower';

        return array_search($tolower($set), array_map($tolower, $this->getAttributeSets()));
    }

    /**
     * Create/update attributes. Assign them to the attribute set
     *
     * @param $data
     */
    protected function prepareAttributes(array &$data)
    {
        $attributeSetId = $this->getAttributeSetIdByName($data['Additional Attributes']['Attribute Set']);

        if (isset($data['Additional Attributes']['Attributes'])) {
            foreach ($data['Additional Attributes']['Attributes'] as $attributeData) {
                $attributeCode = $this->getAttributeCodeByName($attributeData['Attribute']['Name']);

                if (!in_array($attributeCode, $this->processedAttributes)) {
                    if (!$this->getAttributeId($attributeCode)) {

                        $attributeOptions = [
                            'label' => $attributeData['Attribute']['Name'],
                            'type' => $attributeData['Attribute']['Type'] == 'dropdown' ? 'int': 'varchar',
                            'input' => $attributeData['Attribute']['Type'] == 'dropdown' ? 'select': 'text',
                            'required' => false,
                            'user_defined' => true
                        ];

                        $this->eavSetup->addAttribute(
                            $this->getEntityTypeId(),
                            $attributeCode,
                            $attributeOptions
                        );

                        $this->initAttributes();

                    } else {
                        foreach ($attributeData as $field => $value) {
                            $this->eavSetup->updateAttribute(
                                $this->getEntityTypeId(),
                                $attributeCode,
                                $field,
                                $value
                            );
                        }
                    }
                    $this->processedAttributes[] = $attributeCode;
                }

                $attributeId = $this->getAttributeId($attributeCode);
                $attributeGroupId = $this->getAttributeSetGroupId($attributeSetId, self::ATTRIBUTE_GROUP_NAME);

                if (!$attributeGroupId) {
                    $this->eavSetup->addAttributeGroup($this->getEntityTypeId(), $attributeSetId, self::ATTRIBUTE_GROUP_NAME);
                    $attributeGroupId = $this->getAttributeSetGroupId($attributeSetId, self::ATTRIBUTE_GROUP_NAME);
                }

                $this->eavSetup->addAttributeToSet(
                    $this->getEntityTypeId(),
                    $attributeSetId,
                    $attributeGroupId,
                    $attributeId,
                    0
                );
            }
        }
    }

    /**
     * @param array $productData
     */
    protected function prepareAttributeOptions(array &$productData)
    {
        if (isset($productData['Additional Attributes']['Attributes'])) {
            foreach ($productData['Additional Attributes']['Attributes'] as $attributeData) {
                $attributeCode = $this->getAttributeCodeByName($attributeData['Attribute']['Name']);
                $attributeId = array_search($attributeCode, $this->sourceAttributes);
                if ($attributeId !== false && isset($attributeData['Attribute']['Value'])) {
                    $values = (array) $attributeData['Attribute']['Value'];
                    foreach ($values as $value) {

                        $value = $this->mapAttributeValue($attributeCode, $value);
                        $optionId = array_search($value, $this->attributeOptions[$attributeCode]);

                        if ($optionId === false) {
                            $this->newAttributeOptions[$attributeId][] = $value;
                            $this->attributeOptions[$attributeCode][] = $value;
                        }
                    }
                }
            }
        }
    }

    /**
     * @param array $productData
     */
    protected function prepareAttributeValues(array &$productData)
    {

    }

    /**
     * @param $attributeCode
     * @return bool
     */
    public function getAttributeId($attributeCode)
    {
        return array_search($attributeCode, $this->getAttributes());
    }

    /**
     * @return mixed
     */
    private function getAttributes()
    {
        return $this->simpleAttributes + $this->sourceAttributes;
    }

    /**
     * @param array $productData
     * @param Product $product
     * @param bool $collectOptions
     */
    private function processAttributes(array $productData, Product &$product)
    {
        if (isset($productData['Additional Attributes']['Attributes'])) {
            foreach ($productData['Additional Attributes']['Attributes'] as $attributeData) {
                $attributeCode = $this->getAttributeCodeByName($attributeData['Attribute']['Name']);
                $attributeId = array_search($attributeCode, $this->sourceAttributes);
                if ($attributeId !== false && isset($attributeData['Attribute']['Value'])) {
                    $values = (array) $attributeData['Attribute']['Value'];
                    $valuesIds = [];
                    foreach ($values as $value) {

                        $value = $this->mapAttributeValue($attributeCode, $value);
                        $optionId = array_search($value, $this->attributeOptions[$attributeCode]);

                        if ($optionId) {
                            $valuesIds[] = $optionId;
                        }
                    }
                    if (count($valuesIds) == 1) {
                        $valuesIds = current($valuesIds);
                    }
                    $product->setData($attributeCode, $valuesIds);
                } elseif (isset($attributeData['Attribute']['Value'])) {
                    $value = $this->mapAttributeValue($attributeCode, $attributeData['Attribute']['Value']);
                    $product->setData($attributeCode, $value);
                }
            }
        }
    }

    /**
     * @param array $productData
     * @param Product $product
     */
    protected function processStaticAttributes(array $productData, $product)
    {
        foreach (self::STATIC_ATTRIBUTE_GROUPS as $staticGroup) {
            foreach ($productData[$staticGroup] as $attributeName => $value) {

                $attributeCode = $this->getAttributeCodeByName($attributeName);
                $value = $this->mapAttributeValue($attributeCode, $value);
                $attributeId = array_search($attributeCode, $this->sourceAttributes);

                if ($attributeId !== false) {
                    $values = (array) $value;
                    $valuesIds = [];
                    foreach ($values as $value) {

                        $optionId = array_search($value, $this->attributeOptions[$attributeCode]);

                        if ($optionId) {
                            $valuesIds[] = $optionId;
                        }
                    }
                    if (count($valuesIds) == 1) {
                        $valuesIds = current($valuesIds);
                    }
                    $product->setData($attributeCode, $valuesIds);
                } else {
                    $product->setData($attributeCode, $value);
                }
            }
        }
    }

    /**
     * Add missing attribute options
     *
     * @param array $attributes
     */
    private function addAttributeOptions($attributes)
    {
        foreach ($attributes as $attributeId => $options) {
            $attribute = $this->eavAttribute->setEntityTypeId($this->getEntityTypeId());
            $attribute->load($attributeId);
            $optionsData = [];
            foreach ($options as $key => $option) {
                $optionsData['value']['option_' . $key][0] = $option;
                $optionsData['order']['option_' . $key]    = 0;
            }
            $attribute->setData('option', $optionsData);
            $attribute->save();

            $this->attributeOptions[$attribute->getAttributeCode()] =
                $this->arrayToOptionHash(
                    $attribute->getSource()->getAllOptions(),
                    'value',
                    'label',
                    false
                );
        }
    }

    /**
     * Unset attributes which are not passed in the API call
     *
     * @param array $productData
     * @param Product $product
     */
    protected function unsetProductAttributes(array $productData, Product $product)
    {
        $usedAttributes = [];
        if (isset($productData['Additional Attributes']['Attributes'])) {
            foreach ($productData['Additional Attributes']['Attributes'] as $attributeData) {
                $usedAttributes[] = $this->getAttributeCodeByName($attributeData['Attribute']['Name']);
            }
        }

        foreach (self::STATIC_ATTRIBUTE_GROUPS as $staticGroup) {
            foreach ($productData[$staticGroup] as $attributeName => $value) {
                $usedAttributes[] = $this->getAttributeCodeByName($attributeName);
            }
        }

        $usedAttributes = array_merge(array_keys($productData), $usedAttributes);
        if ($product->getId()) {
            $originalAttributes = array_keys($product->getOrigData());
        } else {
            $originalAttributes = [];
        }
        $originalDataAttributeKeys = array_intersect($originalAttributes, $this->getAttributes());
        $attributesToUnset = array_diff($originalDataAttributeKeys, $usedAttributes, self::SYSTEM_ATTRIBUTES);

        foreach ($attributesToUnset as $key) {
            $product->setData($key, null);
        }
    }

    /**
     * @param array $array
     * @param $valueField
     * @param $labelField
     * @return array
     */
    private function arrayToOptionHash(array $array, $valueField, $labelField)
    {
        $result = [];
        foreach ($array as $item) {
            $result[$item[$valueField]]  = $item[$labelField];
        }
        return $result;
    }

    /**
     * Converts an attribute label to a valid attribute code.
     *
     * @param $name
     * @return string
     */
    private function getAttributeCodeByName($name)
    {
        $attributeCode = strtolower(str_replace(" ", "_", trim($name)));

        if (isset(self::ATTRIBUTE_MAP[$attributeCode])) {
            return self::ATTRIBUTE_MAP[$attributeCode];
        }

        return $attributeCode;
    }

    /**
     * Map a attribute value to a value which can be processed by Magento.
     *
     * @param string $attributeCode
     * @param string $value
     * @return string
     */
    private function mapAttributeValue($attributeCode, $value)
    {
        if (isset(self::ATTRIBUTE_VALUE_MAP[$attributeCode])) {
            $value = self::ATTRIBUTE_VALUE_MAP[$attributeCode][$value];
        }

        return trim($value);
    }
}
