<?php

namespace Gudtech\RetailOps\Model\Catalog\Adapter\Product\Type;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ProductRepository;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable as ConfigurableProduct;
use Magento\Eav\Model\AttributeRepository;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Set as AttributeSet;
use Gudtech\RetailOps\Model\Catalog\Adapter;
use Gudtech\RetailOps\Model\Catalog\Adapter\Attribute as AttributeAdapter;

class Configurable extends Adapter
{
    private $associations  = [];
    private $configurableOptions = [];

    private $productRepository;
    private $configurableProduct;
    private $attributeAdapter;
    private $attributeRepository;

    public function __construct(
        ProductRepository $productRepository,
        ConfigurableProduct $configurableProduct,
        AttributeAdapter $attributeAdapter,
        AttributeRepository $attributeRepository,
        AttributeSet $attributeSet
    ) {
        $this->productRepository = $productRepository;
        $this->configurableProduct = $configurableProduct;
        $this->attributeAdapter = $attributeAdapter;
        $this->attributeRepository = $attributeRepository;
        $this->attributeSet = $attributeSet;
    }

    /**
     * @param array $productData
     * @param Product $product
     * @return mixed|void
     */
    public function processData(array &$productData, Product $product)
    {
        $sku = $productData['sku'];
        if (isset($productData['configurable_sku'])) {
            $this->associations[$sku] = $productData['configurable_sku'];
        }

        if (isset($productData['price_changes'])) {
            $this->configurableOptions[$sku] = $productData['price_changes'];
        }
    }

    /**
     * @param array $skuToIdMap
     * @return $this|void
     */
    public function afterDataProcess(array &$skuToIdMap)
    {
        $failedSkus = [];

        if (!empty($this->associations)) {
            $parentChildIds = [];
            $disassociateIds = [];
            foreach ((array) $this->associations as $sku => $parentSkus) {
                $childProductId = $skuToIdMap[$sku];
                if ($parentSkus) {
                    foreach ($parentSkus as $parentSku) {
                        if (isset($skuToIdMap[$parentSku])) {
                            $parentProductId = $skuToIdMap[$parentSku];
                            if (!isset($parentChildIds[$parentProductId])) {
                                $parentChildIds[$parentProductId]['add'] = [];
                            }
                            $parentChildIds[$parentProductId]['add'][] = $childProductId;
                        } else {
                            $failedSkus[$sku] = sprintf('Parent product "%s" not found', $parentSku);
                        }
                    }
                } else {
                    $disassociateIds[] = $childProductId;
                }
            }
            foreach ($disassociateIds as $disassociateId) {
                $parents = $this->configurableProduct->getParentIdsByChild($disassociateId);
                foreach ($parents as $parentId) {
                    if (!isset($parentChildIds[$parentId])) {
                        $parentChildIds[$parentId]['remove'] = [];
                    }
                    $parentChildIds[$parentId]['remove'][] = $disassociateId;
                }
            }
            foreach ($parentChildIds as $parentId => $childIds) {
                try {
                    $configurable = $this->productRepository->getById($parentId);
                    if ($configurable->getTypeId() !== ConfigurableProduct::TYPE_CODE) {
                        throw new \LogicException('Product is not configurable');
                    }
                    $assignedProducts = $configurable->getTypeInstance()->getUsedProductIds($configurable);
                    if (!empty($childIds['add'])) {
                        $assignedProducts = array_merge($assignedProducts, $childIds['add']);
                    }
                    if (!empty($childIds['remove'])) {
                        $assignedProducts = array_diff($assignedProducts, $childIds['remove']);
                    }
                    Mage::getResourceModel('catalog/product_type_configurable')
                                            ->saveProducts($configurable, $assignedProducts);
                } catch (Exception $e) {
                    $failedSkus[$configurable->getSku()] = $e->getMessage();
                }
            }
        }
        if (isset($this->configurableOptions)) {
            $allOptions =  $this->attributeAdapter->getAttributeOptions();
            foreach ($this->configurableOptions as $sku => $configurableAttributes) {
                try {
                    $productId = $skuToIdMap[$sku];

                    $configurable = $this->productRepository->getById($parentId);
                    if ($configurable->getTypeId() !== ConfigurableProduct::TYPE_CODE) {
                        throw new \LogicException('Product is not configurable');
                    }
                    $productType = $configurable->getTypeInstance();
                    $productType->setProduct($configurable);
                    $usedAttributes = [];
                    foreach ($configurableAttributes as $attributeCode => $attribute) {
                        $attributeId = $this->attributesAdapter->findAttribute($attributeCode);
                        if ($attributeId === false) {
                            throw new \LogicException('Attribute "%s" not found', $attributeCode);
                        }
                        $attribute = $this->attributeRepository->get(Product::ENTITY, $attributeCode);
                        $isInSet = $this->attributeSet->getSetInfo([$attribute->getAttributeId()], $configurable->getAttributeSetId());

                        if (!$isInSet[$attributeId]
                            || !$productType->canUseAttribute($attribute)) {
                            throw new \LogicException(sprintf('Attribute "%s" is not assigned to attribute set or cannot be used for configurable products', $attributeCode));
                        }
                        $usedAttributes[] = $attributeId;
                    }
                    $configurableAttributesData = $productType->getConfigurableAttributesAsArray();
                    if (!$configurableAttributesData) {
                        $productType->setUsedProductAttributeIds($usedAttributes);
                        $configurableAttributesData = $productType->getConfigurableAttributesAsArray();
                    } else {
                        foreach ($configurableAttributesData as $key => $attributeData) {
                            if (!in_array($attributeData['attribute_id'], $usedAttributes)) {
                                unset($configurableAttributesData[$key]);
                            }
                        }
                    }
                    foreach ($configurableAttributesData as &$attributeData) {
                        if (isset($configurableAttributes[$attributeData['attribute_code']])) {
                            $attribute = $configurableAttributes[$attributeData['attribute_code']];
                        } else {
                            $attribute = [];
                        }
                        $attributeData['label'] = isset($attribute['label']) ? $attribute['label'] : $attributeData['frontend_label'];
                        $attributeData['position'] = isset($attribute['position']) ? $attribute['position'] : 0;
                        if (isset($attribute['options'])) {
                            foreach ($attribute['options'] as $option => $priceChange) {
                                if (isset($allOptions[$attributeCode][$option])) {
                                    $optionId = $allOptions[$attributeCode][$option];
                                    $isPercent = 0;
                                    if (false !== strpos($priceChange, '%')) {
                                        $isPercent = 1;
                                    }
                                    $priceChange = preg_replace('/[^0-9\.,-]/', '', $priceChange);
                                    $priceChange = (float) str_replace(',', '.', $priceChange);
                                    $attributeData['values'][$optionId] = [
                                        'value_index'   => $optionId,
                                        'is_percent'    => $isPercent,
                                        'pricing_value' => $priceChange,
                                    ];
                                }
                            }
                        }
                    }
                    $configurable->setConfigurableAttributesData($configurableAttributesData);
                    $this->productRepository->save($configurable);
                    $configurable->clearInstance();
                } catch (Exception $e) {
                    $failedSkus[$configurable->getSku()] = $e->getMessage();
                }
            }
        }

        if ($failedSkus) {
            $finalMessage = [];
            foreach ($failedSkus as $sku => $message) {
                $finalMessage[] = sprintf('sku: "%s", error: "%s"', $sku, $message);
            }
            $finalMessage = implode('; ', $finalMessage);
            throw new \LogicException('Configurable data is not saved for: ' . $finalMessage);
        }
    }
}
