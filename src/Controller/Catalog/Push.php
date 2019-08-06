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

    /**
     * @var array
     */
    private $events = [];

    /**
     * @var null|string|array
     */
    private $responseEvents = [];

    /**
     * @var CatalogPush
     */
    private $catalogPush;

    /**
     * Push constructor.$catalogPush
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
        try {

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

            if (isset($feedData['data']['Magento Simple Product'])) {
                foreach($feedData['data']['Magento Simple Product'] as $item) {
                    $productData[] = $item["feed_data"];
                }
            }

            $this->catalogPush->beforeDataPrepare();

            foreach ($productData as $key => &$data) {
                try {
                    $this->logger->addInfo("Preparing data for SKU: ". $data['General']['SKU']);
                    $this->catalogPush->prepareData($data);
                } catch (\Exception $exception) {
                    $this->logger->addCritical(
                        "Preparing data failed for SKU ". $data['General']['SKU'] .": ". $exception->getMessage()
                    );
                    unset($productData[$key]);
                }
            }
            $this->catalogPush->afterDataPrepare();
            $this->catalogPush->beforeDataProcess();

            foreach ($productData as &$data) {
                try {
                    $this->logger->addInfo("Processing data for SKU: ". $data['General']['SKU']);
                    $this->catalogPush->processData($data);
                } catch (\Exception $exception) {
                    $this->logger->addCritical(
                        "Processing data failed for SKU ". $data['General']['SKU'] .": ". $exception->getMessage()
                    );
                }
            }

            $this->catalogPush->afterDataProcess();

        } catch (\Exception $exception) {
            $this->logger->addCritical($exception->getMessage());
            $this->responseEvents = [];
            $this->status = 500;
            $this->error = $exception;
            parent::execute();

        } finally {
            $this->responseEvents['events'] = [];
            foreach ($this->events as $event) {
                $this->responseEvents['events'][] = $event;
            }

            $this->getResponse()->representJson(json_encode($this->responseEvents));
            $this->getResponse()->setStatusCode($this->status);
            parent::execute();

            return $this->getResponse();
        }
    }
}
