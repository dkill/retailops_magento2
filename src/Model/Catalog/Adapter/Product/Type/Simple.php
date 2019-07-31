<?php

namespace Gudtech\RetailOps\Model\Catalog\Adapter\Product\Type;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ProductRepository;
use Magento\Catalog\Model\Product\Type as ProductType;
use Magento\Store\Model\StoreManager;
use Magento\Store\Model\WebsiteRepository;
use Magento\Framework\Exception\InputException;
use Gudtech\RetailOps\Model\Catalog\Adapter;

class Simple extends Adapter
{
    const VISIBILITY_VISIBLE = 'Visible';
    const STATUS_ENABLED = 'Enabled';

    /**
     * @var ProductType
     */
    private $productType;
    private $productRepository;
    private $websiteRepository;
    private $storeManager;

    public function __construct(
        ProductType $productType,
        ProductRepository $productRepository,
        WebsiteRepository $websiteRepository,
        StoreManager $storeManager
    ) {
        $this->productType = $productType;
        $this->productRepository = $productRepository;
        $this->websiteRepository = $websiteRepository;
        $this->storeManager = $storeManager;
    }

    /**
     * @param array $data
     * @throws InputException
     */
    public function validateData(array &$data)
    {
        if (empty($data['General']['SKU'])) {
            throw new InputException('Product SKU is missing.');
        }
    }

    /**
     * @param array $productData
     * @param Product $product
     * @return mixed
     */
    public function processData(array &$productData, Product $product)
    {
        $attributeSetId = $productData['attribute_set_id'];

        $product->setAttributeSetId($attributeSetId);

        if (isset($productData['stock_data']) && is_array($productData['stock_data'])) {
            $productData['stock_data']['qty'] = $productData['stock_data']['quantity'];
        }

        if (!$product->getId()) {
            if (empty($productData['type_id'])) {
                throw new \LogicException('Product type is not specified');
            }
            $type = $productData['type_id'];
            $this->checkProductTypeExists($type);
            $product->setTypeId($type);
            $product->setSku($productData['General']['SKU']);
            if (!isset($productData['stock_data']) || !is_array($productData['stock_data'])) {
                //Set default stock_data if not exist in product data
                $product->setStockData(['use_config_manage_stock' => 0]);
            }
        }

        $this->prepareDataForSave($product, $productData);

        if (is_array($errors = $product->validate())) {
            throw new \LogicException(implode($errors));
        }

        $product = $this->productRepository->save($product);

        $productData['product_id'] = $product->getId();

        return $product->getId();
    }

    /**
    *  Set additional data before product saved
    *
    *  @param    Product $product
    *  @param    array $productData
    *  @return   object
    */
    protected function prepareDataForSave($product, $productData)
    {
        $product->setName(trim($productData['General']['Name']));
        $product->setDescription(trim($productData['General']['Description']));
        $product->setShortDescription(trim($productData['General']['Short Description']));
        $product->setUrlKey(trim($productData['General']['Url Key']));

        if ($productData['General']['Visibility'] == self::VISIBILITY_VISIBLE) {
            $product->setVisibilty();
        } else {
            $product->setVisibility();
        }

        if ($productData['General']['Visibility'] == self::STATUS_ENABLED) {
            $product->setVisibilty();
        } else {
            $product->setVisibility();
        }


        $product->setMetaDescription(trim($productData['Meta Information']['Meta Description']));
        $product->setMetaKeywords(trim($productData['Meta Information']['Meta Keywords']));
        $product->setMetaTitle(trim($productData['Meta Information']['Meta Title']));

        foreach ($productData['Additional Attributes']['Attributes'] as $attribute) {

        }

        if (isset($productData['website_ids']) && is_array($productData['website_ids'])) {
            $product->setWebsiteIds($productData['website_ids']);
        }

        if (isset($productData['websites']) && is_array($productData['websites'])) {
            foreach ($productData['websites'] as &$website) {
                if (is_string($website)) {
                    $website = $this->websiteRepository->get($website)->getId();
                }
            }
            $product->setWebsiteIds($productData['websites']);
        }

        if ($this->storeManager->isSingleStoreMode()) {
            $product->setWebsiteIds([$this->storeManager->getStore()->getWebsiteId()]);
        }

        if (isset($productData['stock_data']) && is_array($productData['stock_data'])) {
            $product->setStockData($productData['stock_data']);
        }

        if (isset($productData['tier_price']) && is_array($productData['tier_price'])) {
            $product->setTierPrices($productData['tier_price']);
        }
    }

    /**
     * Check if product type exists
     *
     * @param  $productType
     * @throw InputException
     * @return void
     */
    protected function checkProductTypeExists($productType)
    {
        if (!in_array($productType, $this->productType->toOptionArray())) {
            throw new InputException('Product type not exists');
        }
    }
}
