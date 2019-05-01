<?php

namespace RetailOps\Api\Model\Catalog\Adapter\Product\Type;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ProductRepository;
use Magento\Catalog\Model\Product\Type as ProductType;
use Magento\Store\Model\StoreManager;
use Magento\Store\Model\WebsiteRepository;
use Magento\Framework\Exception\InputException;
use RetailOps\Api\Model\Catalog\Adapter;

class Simple extends Adapter
{
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
        if (empty($data['sku'])) {
            throw new InputException('Product SKU is missing.');
        }
    }

    /**
     * @param array $productData
     * @param Product $product
     * @return mixed
     */
    public function processData(array &$productData, $product)
    {
        $oldProductUrl = $product->getUrlKey();
        $attributeSetId = $productData['attribute_set_id'];
        $sku = $productData['sku'];

        $product->setAttributeSetId($attributeSetId);

        if (isset($productData['stock_data']) && is_array($productData['stock_data'])) {
            //$resourceModel = Mage::getResourceModel('retailops_api/api');
            //$orderQtys = $resourceModel->getRetailopsNonretrievedQtys();
            //$stockObj = $resourceModel->subtractNonretrievedQtys($orderQtys, $productData['stock_data']);
            //$stockObj = $resourceModel->refSubtractQtys($productData['stock_data']);
            //$productData['stock_data'] = $stockObj->getData();
        }

        if (!$product->getId()) {
            if (empty($productData['type_id'])) {
                throw new \LogicException('Product type is not specified');
            }
            $type = $productData['type_id'];
            $this->checkProductTypeExists($type);
            $product->setTypeId($type)->setSku($sku);
            if (!isset($productData['stock_data']) || !is_array($productData['stock_data'])) {
                //Set default stock_data if not exist in product data
                $product->setStockData(['use_config_manage_stock' => 0]);
            }
        }

        $this->prepareDataForSave($product, $productData);

        if (is_array($errors = $product->validate())) {
            throw new \LogicException(implode($errors));
        }

        $this->productRepository->save($product);

        // New URL inserted in mass-redirect table
        // $this->InsertInMassRedirect($product, $productData, $oldProductUrl);

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
        $product->addData($productData);

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

    // New URL inserted in massredirect table
    public function InsertInMassRedirect($product, $productData, $ProductOldUrl)
    {
        if (isset($productData['url_key'])) {
            $NewUrlKey = Mage::getResourceModel('catalog/url')->getCategoryModel()->formatUrlKey($productData['url_key']);

            // Load product data from sku
            if ($ProductOldUrl) {
                if ($NewUrlKey != $ProductOldUrl) {
                    $massredirect['new_url'] = $NewUrlKey . ".html";
                    $massredirect['old_url'] = $ProductOldUrl;
                    $massredirect['sku'] = $productData['sku'];
                    $massredirect['website_id'] = $product['website_ids'][0];
                    $table = Mage::getSingleton('core/resource')->getTableName('massredirect/massredirect');
                    $resource = Mage::getSingleton('core/resource');
                    $writeConnection = $resource->getConnection('core_write');
                    try {
                        $writeConnection->insert($table, $massredirect);
                    } catch (Exception $e) {
                        $this->_throwException($e->getMessage(), 'error_saving_mass_redirect');
                    }
                }
            }
        }
    }
}
