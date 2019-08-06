<?php

namespace Gudtech\RetailOps\Model\Catalog\Adapter\Product\Type;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\ProductRepository;
use Magento\Eav\Model\AttributeRepository;
use Magento\Eav\Model\Entity;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Set as AttributeSet;
use Gudtech\RetailOps\Model\Catalog\Adapter;
use Gudtech\RetailOps\Model\Catalog\Adapter\Product\Attribute as AttributeAdapter;

class Configurable extends Adapter
{
    /**
     * @var string
     */
    const CONFIGURABLE_ATTRIBUTE_NAME = 'Configurable SKU';

    /**
     * @var array
     */
    private $associations  = [];

    /**
     * @var array
     */
    private $configurableAttributes = [];

    /**
     * @var ProductRepository
     */
    private $productRepository;

    /**
     * @var array
     */
    private $configurableProductSku = [];

    /**
     * @var AttributeAdapter
     */
    private $attributeAdapter;

    /**
     * @var AttributeRepository
     */
    private $attributeRepository;

    /**
     * @var ProductFactory
     */
    private $productFactory;

    /**
     * @var Entity
     */
    private $eavEntity;

    /**
     * @var AttributeSet
     */
    private $attributeSet;

    /**
     * Configurable product constructor.
     *
     * @param ProductRepository $productRepository
     * @param ProductFactory $productFactory
     * @param Entity $eavEntity
     * @param AttributeAdapter $attributeAdapter
     * @param AttributeRepository $attributeRepository
     * @param AttributeSet $attributeSet
     */
    public function __construct(
        ProductRepository $productRepository,
        ProductFactory $productFactory,
        Entity $eavEntity,
        AttributeAdapter $attributeAdapter,
        AttributeRepository $attributeRepository,
        AttributeSet $attributeSet
    ) {
        $this->productRepository = $productRepository;
        $this->productFactory = $productFactory;
        $this->eavEntity = $eavEntity;
        $this->attributeAdapter = $attributeAdapter;
        $this->attributeRepository = $attributeRepository;
        $this->attributeSet = $attributeSet;
    }

    /**
     * @param array $productData
     * @param Product $product
     * @return mixed|void
     */
    public function processData(array $productData, Product $product)
    {
        $sku = $productData['General']['SKU'];

        if (isset($productData['Configurable Attributes'])) {
            $this->configurableProductSku[] = $sku;
            $this->configurableAttributes[$sku] = [];

            $configurableAttributes = explode(",", $productData['Configurable Attributes']);

            foreach($configurableAttributes as $configurableAttribute) {
                $this->configurableAttributes[$sku][] = trim($configurableAttribute);
            }
        } else {
            $configurableSku = $this->getConfigurableSku($productData);

            if ($configurableSku) {
                if (!isset($this->associations[$configurableSku])) {
                    $this->associations[$configurableSku] = [];
                }
                $this->associations[$configurableSku][] = $sku;
            }
        }
    }

    /**
     *
     * @return $this|void
     */
    public function afterDataProcess()
    {
        foreach($this->configurableProductSku as $configurableProductSku)
        {
            $configurableAttributeIds = [];
            $configurableProduct = $this->productRepository->get($configurableProductSku);

            foreach ($this->configurableAttributes[$configurableProductSku] as $attributeCode) {
                $attribute = $this->attributeRepository->get($this->getEntityTypeId(), $attributeCode);
                $configurableAttributeIds[] = $attribute->getAttributeId();
            }

            if ($configurableAttributeIds) {

                $configurableProduct->getTypeInstance()->setUsedProductAttributeIds(
                    $configurableAttributeIds,
                    $configurableProduct
                );

                $configurableAttributesData = $configurableProduct->getTypeInstance()->getConfigurableAttributesAsArray(
                    $configurableProduct
                );

                $configurableProduct->setCanSaveConfigurableAttributes(true);
                $configurableProduct->setConfigurableAttributesData($configurableAttributesData);

                $simpleProductIds = [];
                $product = $this->productFactory->create();

                foreach ($this->associations[$configurableProductSku] as $simpleProductSku) {
                    if ($productId = $product->getIdBySku($simpleProductSku)) {
                        $simpleProductIds[] = $productId;
                    }
                }

                $configurableProduct->setAssociatedProductIds($simpleProductIds);
                $this->productRepository->save($configurableProduct);
            }
        }
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
     * @param $productData
     * @return string
     */
    private function getConfigurableSku($productData)
    {
        $configurableSku = '';

        foreach($productData['Additional Attributes']['Attributes'] as $attribute) {
            if ($attribute['Attribute']['Name'] == self::CONFIGURABLE_ATTRIBUTE_NAME) {
                $configurableSku = trim($attribute['Attribute']['Value']);
            }
        }

        return $configurableSku;
    }
}
