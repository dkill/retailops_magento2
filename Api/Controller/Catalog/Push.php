<?php

namespace RetailOps\Api\Controller\Catalog;

use Magento\Framework\App\Config\ScopeConfigInterface;
use RetailOps\Api\Controller\RetailOps;

/**
 * Catalog push controller class
 *
 */
class Push extends RetailOps
{
    const SERVICENAME = 'catalog';

    const ENABLE = 'retailops/retailops_feed/catalog_push';

    /**
     * @var string
     */
    protected $areaName = self::BEFOREPULL . self::SERVICENAME;

    private $events = [];

    private $catalogPush;

    private $config;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        ScopeConfigInterface $config,
        \RetailOps\Api\Model\Catalog\Push $catalogPush,
        \RetailOps\Api\Model\Logger\Monolog $logger
    ) {
        $this->config = $config;
        $this->catalogPush = $catalogPush;
        $this->logger = $logger;

        parent::__construct($context);
    }

    public function execute()
    {
        $productsData = [];
        $result = [];
        $result['records'] = [];
        $processedSkus = [];

        try {
            if (!$this->config->getValue(self::ENABLE)) {
                throw new \LogicException('API endpoint has been disabled');
            }

            $this->catalogPush->beforeDataPrepare();

            foreach ($productsData as $key => $data) {
                $dataObj = new Varien_Object($data);
                $data = $dataObj->getData();
                $processedSkus[] = $data['sku'];
                $this->catalogPush->prepareData($data);
            }

            $this->catalogPush->afterDataPrepare();

            foreach ($productsData as $key => $data) {
                $dataObj = new Varien_Object($data);
                $data = $dataObj->getData();
                $processedSkus[] = $data['sku'];
                $this->catalogPush->validateData($data);
            }

            $this->catalogPush->beforeDataProcess();

            foreach ($productsData as $data) {
                $dataObj = new Varien_Object($data);
                $data = $dataObj->getData();
                $this->catalogPush->processData($data);
            }

            $this->catalogPush->afterDataProcess();

            foreach ($processedSkus as $sku) {
                $r = [];
                $r['sku'] = $sku;
                $r['status'] = RetailOps_Api_Helper_Data::API_STATUS_SUCCESS;
                if (!empty($this->_errors[$sku])) {
                    $r['status'] = RetailOps_Api_Helper_Data::API_STATUS_FAIL;
                    $r['errors'] = $this->_errors[$sku];
                }
                $result['records'][] = $r;
            }
            if (isset($this->_errors['global'])) {
                $result['global_errors'] = $this->_errors['global'];
            }


        } catch (Exception $e) {
            $this->_addError(new RetailOps_Api_Model_Catalog_Exception($e->getMessage()));
        }



        $this->response['events'] = [];
        foreach ($this->events as $event) {
            $this->response['events'][] = $event;
        }
        $this->getResponse()->representJson(json_encode($this->response));
        $this->getResponse()->setStatusCode('200');
        parent::execute();
        return $this->getResponse();
    }
}
