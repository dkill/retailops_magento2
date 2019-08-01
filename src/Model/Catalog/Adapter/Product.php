<?php

namespace Gudtech\RetailOps\Model\Catalog\Adapter;

use Magento\Catalog\Model\Product as ProductModel;
use Magento\Catalog\Model\ProductRepository;
use Magento\Catalog\Model\Product\Type as ProductType;
use Magento\Store\Model\StoreManager;
use Magento\Store\Model\WebsiteRepository;
use Magento\Framework\Exception\InputException;
use Magento\Catalog\Model\Product\Visibility;
use Gudtech\RetailOps\Model\Catalog\Adapter;
use Magento\Catalog\Model\Product\Attribute\Source\Status;

class Product extends Adapter
{
    const STATUS_ATTRIBUTE_MAP = [
        'Enabled' => Status::STATUS_ENABLED,
        'Disabled' => Status::STATUS_DISABLED
    ];

    const VISIBILITY_ATTRIBUTE_MAPPING = [
        'Visible' => Visibility::VISIBILITY_BOTH,
        'Invisible' => Visibility::VISIBILITY_NONE
    ];

    private $productRepository;
    private $websiteRepository;
    private $storeManager;
    private $websiteIds = [];

    public function __construct(
        ProductRepository $productRepository,
        WebsiteRepository $websiteRepository,
        StoreManager $storeManager
    ) {
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
     * @param ProductModel $product
     * @return mixed
     */
    public function processData(array &$productData, ProductModel $product)
    {
        if (!$product->getId()) {
            $product->setTypeId(ProductType::TYPE_SIMPLE);
            $product->setSku($productData['General']['SKU']);
        }

        $this->prepareDataForSave($product, $productData);

        if (is_array($errors = $product->validate())) {
            throw new \LogicException(implode($errors));
        }

        $product = $this->productRepository->save($product);

        return $product->getId();
    }

    /**
     *  Set additional data before product saved
     *
     *  @param    ProductModel $product
     *  @param    array $productData
     *  @return   object
     */
    private function prepareDataForSave($product, $productData)
    {
        if (isset($productData['Websites']) && is_array($productData['Websites'])) {
            $websiteIds = [];
            foreach ($productData['Websites'] as $website) {
                $websiteIds[] = $this->websiteRepository->get($website)->getId();
            }
            $product->setWebsiteIds($websiteIds);
        }

        if ($this->storeManager->isSingleStoreMode()) {
            $product->setWebsiteIds([$this->storeManager->getStore()->getWebsiteId()]);
        }

        /**
         * @todo stock inventory needs to be implemented when it's available in the data.
         *
         * if (isset($productData['stock_data']) && is_array($productData['stock_data'])) {
         *     $product->setStockData($productData['stock_data']);
         * }
         */

        /**
         * Tier prices not being used
         *
         * if (isset($productData['tier_price']) && is_array($productData['tier_price'])) {
         *   $product->setTierPrices($productData['tier_price']);
         * }
         */

    }
}
