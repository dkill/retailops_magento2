<?php

namespace Gudtech\RetailOps\Controller\Catalog;

use Gudtech\RetailOps\Controller\AbstractController;
use Gudtech\RetailOps\Model\Logger\Monolog;
use Gudtech\RetailOps\Model\Catalog\Push as CatalogPush;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * Catalog push controller class
 *
 */
class Push extends AbstractController
{
    const SERVICENAME = 'catalog';

    /**
     * @var string
     */
    protected $areaName = self::BEFOREPULL . self::SERVICENAME;

    private $events = [];

    /**
     * @var CatalogPush
     */
    private $catalogPush;

    /**
     * Push constructor.
     *
     * @param Context $context
     * @param Monolog $logger
     * @param ScopeConfigInterface $config
     * @param Push $catalogPush
     */
    public function __construct(
        Context $context,
        Monolog $logger,
        ScopeConfigInterface $config,
        CatalogPush $catalogPush
    ) {
        $this->logger = $logger;
        $this->catalogPush = $catalogPush;

        parent::__construct($context, $config);
    }

    public function execute()
    {
        $result = [];
        $result['records'] = [];
        $processedSkus = [];
        $productData = [];

        $feedData = $this->getRequest()->getPost('template_output');

        if (isset($feedData['data']['Magento Configurable Product'])) {
            foreach($feedData['data']['Magento Configurable Product'] as $item) {
                $productData[] = $item["feed_data"];
            }
        }

        if (isset($feedData['data']['Magento Configurable Product'])) {
            foreach($feedData['data']['Magento Configurable Product'] as $item) {
                $productData[] = $item["feed_data"];
            }
        }

        try {
            $this->catalogPush->beforeDataPrepare();

            foreach ($productData as $key => $data) {
                try {
                    $dataObj = new Varien_Object($data);
                    $data = $dataObj->getData();
                    $processedSkus[] = $data['sku'];
                    $this->catalogPush->prepareData($data);
                } catch (RetailOps_Api_Model_Catalog_Exception $e) {
                    unset($productData[$key]);
                    $this->_addError($e);
                }
            }
            $this->catalogPush->afterDataPrepare();
            $this->catalogPush->beforeDataProcess();

            foreach ($productData as $data) {
                try {
                    $dataObj = new Varien_Object($data);
                    Mage::dispatchEvent('retailops_catalog_push_data_process_before', array('data' => $dataObj));
                    $data = $dataObj->getData();
                    if (isset($data['url_key']))
                    {
                        Mage::log($data['url_key'],null,'catelogPush.log',true);
                        // $data['save_rewrites_history'] = true;
                    }
                    $this->catalogPush->processData($data);
                    //Mage::dispatchEvent('retailops_catalog_push_data_process_after', array('data' => $dataObj));
                } catch (RetailOps_Api_Model_Catalog_Exception $e) {
                    $this->_addError($e);
                }
            }
            $this->catalogPush->afterDataProcess();
        } catch (Exception $e) {
            $this->_addError(new RetailOps_Api_Model_Catalog_Exception($e->getMessage()));
        }

        foreach ($processedSkus as $sku) {
            $r = array();
            $r['sku'] = $sku;
            $r['status'] = RetailOps_Api_Helper_Data::API_STATUS_SUCCESS;
            if (!empty($this->_errors[$sku])) {
                $r['status'] = RetailOps_Api_Helper_Data::API_STATUS_FAIL;
                $r['errors'] = $this->_errors[$sku];
            }
            $result['records'][] = $r;
        }

        $this->responseEvents['events'] = [];
        foreach ($this->events as $event) {
            $this->responseEvents['events'][] = $event;
        }
        $this->getResponse()->representJson(json_encode($this->responseEvents));
        $this->getResponse()->setStatusCode('200');
        parent::execute();
        return $this->getResponse();
    }
}
