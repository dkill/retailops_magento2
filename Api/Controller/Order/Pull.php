<?php

namespace RetailOps\Api\Controller\Order;

use Magento\Framework\App\ObjectManager;
use \RetailOps\Api\Controller\RetailOps;

/**
 * Pull controller action class.
 *
 */
class Pull extends RetailOps
{
    const SERVICENAME = 'order';
    const ORDERS_PER_REQUEST_MAXIMUM = 50;
    const ORDERS_PER_REQUEST_MINIMUM = 1;
    const ENABLE = 'retailops/retailops_feed/order_pull';
    const COUNT_ORDERS_PER_REQUEST = 'retailops/retailops/order_count';

    /**
     * @var string
     */
    protected $areaName = self::BEFOREPULL.self::SERVICENAME;

    /**
     * @var \\RetailOps\Model\Pull\OrderFactory
     */
    protected $orderFactory;

    /**
     * @var null|string|array
     */
    protected $response;

    /**
     * @var \\RetailOps\Logger\Logger
     */
    protected $logger;

    /**
     * @var string|int
     */
    protected $status = 200;

    public function execute()
    {
        try {
            $scopeConfig = $this->_objectManager->get(\Magento\Framework\App\Config\ScopeConfigInterface::class);
            if (!$scopeConfig->getValue(self::ENABLE)) {
                throw new \LogicException('API endpoint has been disabled');
            }
            $orderFactory = $this->orderFactory->create();
            $pageToken = $this->getRequest()->getParam('page_token');
            $postData = $this->getRequest()->getPost();
            $countOfOrders = $scopeConfig->getValue(self::COUNT_ORDERS_PER_REQUEST);
            if ($countOfOrders > self::ORDERS_PER_REQUEST_MAXIMUM) {
                $countOfOrders = self::ORDERS_PER_REQUEST_MAXIMUM;
            }
            if ($countOfOrders < self::ORDERS_PER_REQUEST_MINIMUM) {
                $countOfOrders = self::ORDERS_PER_REQUEST_MINIMUM;
            }
            $response = $orderFactory->getOrders($pageToken, $countOfOrders, $postData);
            $this->response = $response;
        } catch (\Exception $exception) {
            print $exception;

            $this->logger->addCritical($exception->getMessage());
            $this->response = [];
            $this->status = 500;
            $this->error = $exception;
            parent::execute();
        } finally {
            $this->getResponse()->representJson(json_encode($this->response));
            $this->getResponse()->setStatusCode($this->status);
            parent::execute();
        }
    }

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \RetailOps\Api\Model\Pull\OrderFactory $orderFactory,
        \RetailOps\Api\Model\Logger\Monolog $logger
    ) {
        $this->orderFactory = $orderFactory;
        $this->logger = $logger;
        parent::__construct($context);
    }
}
