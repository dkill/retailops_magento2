<?php

namespace Gudtech\RetailOps\Model\Catalog\Adapter\Product\Type;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\ProductRepository;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable as ConfigurableProduct;
use Magento\Eav\Model\AttributeRepository;
use Magento\Eav\Model\Entity;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Set as AttributeSet;
use Gudtech\RetailOps\Model\Catalog\Adapter;
use Gudtech\RetailOps\Model\Catalog\Adapter\Product\Attribute as AttributeAdapter;

class Configurable extends Adapter
{
    private $associations  = [];

    /**
     * @var array
     */
    private $configurableAttributes = [];

    private $productRepository;
    private $configurableProduct;
    private $attributeAdapter;
    private $attributeRepository;

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
            $this->configurableProduct = $product;
            $configurableAttributes = explode(",", $productData['Configurable Attributes']);

            foreach($configurableAttributes as $configurableAttribute) {
                $this->configurableAttributes[] = trim($configurableAttribute);
            }
        } else {
            $this->associations[] = $productData['General']['SKU'];
        }
    }

    /**
     *
     * @return $this|void
     */
    public function afterDataProcess()
    {
        $configurableAttributeIds = [];

        foreach ($this->configurableAttributes as $attributeCode) {
            $attribute = $this->attributeRepository->get(4, $attributeCode);
            $configurableAttributeIds[] = $attribute->getAttributeId();
        }

        if ($configurableAttributeIds) {

            $this->configurableProduct->getTypeInstance()->setUsedProductAttributeIds(
                $configurableAttributeIds,
                $this->configurableProduct
            );

            $configurableAttributesData = $this->configurableProduct->getTypeInstance()->getConfigurableAttributesAsArray(
                $this->configurableProduct
            );

            $this->configurableProduct->setCanSaveConfigurableAttributes(true);
            $this->configurableProduct->setConfigurableAttributesData($configurableAttributesData);

            $simpleProductIds = [];
            $product = $this->productFactory->create();

            foreach ($this->associations as $simpleProductSku) {
                if ($productId = $product->getIdBySku($simpleProductSku)) {
                    $simpleProductIds[] = $productId;
                }
            }

            $this->configurableProduct->setAssociatedProductIds($simpleProductIds);
            $this->productRepository->save($this->configurableProduct);
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
}
