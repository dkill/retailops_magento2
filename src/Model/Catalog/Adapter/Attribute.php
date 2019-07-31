<?php

namespace Gudtech\RetailOps\Model\Catalog\Adapter;

use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Catalog\Model\Product;
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

class Attribute extends Adapter
{
    /**
     * Attributes to skip while unsetting missing attributes
     *
     * @var array
     */
    const SYSTEM_ATTRIBUTES = ['has_options', 'required_options', 'media_gallery'];

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
    public function prepareData(array &$data)
    {
        $this->prepareAttributeSet($data);
        $this->prepareAttributes($data);
        $this->processAttributes($data, true);

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
    public function processData(array &$productData, Product $product)
    {
        $productData['attribute_set_id'] = $this->getAttributeSetIdByName($productData['attribute_set']);
        $this->processStaticAttributes($productData);
        $this->processAttributes($productData);
        if ($product->getId() &&
            (!isset($productData['unset_other_attribute']) || $productData['unset_other_attribute'])
        ) {
            $this->unsetProductAttributes($productData, $product);
        }
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
        foreach ($this->attritbuteGroupCollection as $group) {
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
    private function prepareAttributeSet(array &$data)
    {
        if (!empty($data['attribute_set'])) {
            $attributeSet = $data['attribute_set'];
            $attributeSetId = $this->getAttributeSetIdByName($attributeSet);
            if ($attributeSetId === false) {
                $attributeSetId = $this->createAttributeSet($attributeSet, $data['sku']);
                $this->attributeSets[$attributeSetId] = $attributeSet;
            }
        } else {
            throw new InputException("Attribute set not provided for SKU: " . $data['sku']);
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
        $attributeSetId = $data['attribute_set_id'];

        if (isset($data['attributes'])) {
            foreach ($data['attributes'] as $attributeData) {
                if (!in_array($attributeData['attribute_code'], $this->processedAttributes)) {
                    if (!$this->getAttributeId($attributeData['attribute_code'])) {
                        if (!$attributeData['no_update_if_exists']) {
                            foreach ($attributeData as $field => $value) {
                                $this->eavSetup->updateAttribute(
                                    $this->getEntityTypeId(),
                                    $attributeData['attribute_code'],
                                    $field,
                                    $value
                                );
                            }
                        }
                    } else {
                        $this->eavSetup->addAttribute(
                            $this->getEntityTypeId(),
                            $attributeData['attribute_code'],
                            $attributeData
                        );

                        $this->initAttributes();
                    }
                    $this->processedAttributes[] = $attributeData['attribute_code'];
                }

                $attributeId = $this->getAttributeId($attributeData['attribute_code']);
                $attributeGroup = $attributeData['group_name'];
                $attributeGroupId = $this->getAttributeSetGroupId($attributeSetId, $attributeGroup);

                if (!$attributeGroupId) {
                    $this->eavSetup->addAttributeGroup($this->getEntityTypeId(), $attributeSetId, $attributeGroup);
                    $attributeGroupId = $this->getAttributeSetGroupId($attributeSetId, $attributeGroup);
                }

                $sortOrder = isset($attributeData['sort_order']) ? $attributeData['sort_order'] : 0;
                $this->eavSetup->addAttributeToSet(
                    $this->getEntityTypeId(),
                    $attributeSetId,
                    $attributeGroupId,
                    $attributeId,
                    $sortOrder
                );
            }
        }
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
     * @param $productData
     * @param bool $collectOptions
     */
    private function processAttributes(&$productData, $collectOptions = false)
    {
        if (isset($productData['attributes'])) {
            foreach ($productData['attributes'] as $attributeData) {
                $code = $attributeData['attribute_code'];
                $attributeId = array_search($code, $this->sourceAttributes);
                if ($attributeId !== false && isset($attributeData['value'])) {
                    $values = (array) $attributeData['value'];
                    if ($collectOptions) {
                        foreach ($values as $value) {
                            if (!isset($this->attributeOptions[$code][$value])) {
                                $this->newAttributeOptions[$attributeId][] = $value;
                                $this->attributeOptions[$code][$value] = true;
                            }
                        }
                    } else {
                        $valuesIds = [];
                        foreach ($values as $value) {
                            if (isset($this->attributeOptions[$code][$value])) {
                                $valuesIds[] = $this->attributeOptions[$code][$value];
                            }
                        }
                        if (count($valuesIds) == 1) {
                            $valuesIds = current($valuesIds);
                        }
                        $productData[$code] = $valuesIds;
                    }
                } elseif (isset($attributeData['value'])) {
                    $productData[$code] = $attributeData['value'];
                }
            }
        }
    }

    /**
     * @param $productData
     */
    protected function processStaticAttributes(&$productData)
    {
        foreach ($productData as $code => $value) {
            $attributeId = array_search($code, $this->sourceAttributes);
            if ($attributeId !== false) {
                if (isset($this->attributeOptions[$code][$value])) {
                    $realValue = $this->attributeOptions[$code][$value];
                    $productData[$code] = $realValue;
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
    protected function unsetProductAttributes(array &$productData, Product $product)
    {
        $usedAttributes = [];
        if (isset($productData['attributes'])) {
            foreach ($productData['attributes'] as $attributeData) {
                $usedAttributes[] = $attributeData['attribute_code'];
            }
        }
        $usedAttributes = array_merge(array_keys($productData), $usedAttributes);
        $originalAttributes = array_keys($product->getOrigData());
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
}
