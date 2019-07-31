<?php

namespace Gudtech\RetailOps\Controller\Order;

use Gudtech\RetailOps\Model\Logger\Monolog;
use Gudtech\RetailOps\Model\Pull\OrderFactory;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Gudtech\RetailOps\Controller\AbstractController;

/**
 * Pull controller action class.
 *
 */
class Pull extends AbstractController
{
    const SERVICENAME = 'order';
    const ORDERS_PER_REQUEST_MAXIMUM = 50;
    const ORDERS_PER_REQUEST_MINIMUM = 1;
    const ENABLE = 'retailops/retailops_feed/order_pull';
    const COUNT_ORDERS_PER_REQUEST = 'retailops/retailops/order_count';

    /**
     * @var string
     */
    protected $areaName = self::BEFOREPULL . self::SERVICENAME;

    /**
     * @var \\RetailOps\Model\Pull\OrderFactory
     */
    protected $orderFactory;

    /**
     * @var null|string|array
     */
    protected $responseEvents;

    /**
     * @var \\RetailOps\Logger\Logger
     */
    protected $logger;

    /**
     * @var string|int
     */
    protected $status = 200;

    /**
     * Pull constructor.
     *
     * @param Context $context
     * @param OrderFactory $orderFactory
     * @param Monolog $logger
     * @param ScopeConfigInterface $config
     */
    public function __construct(
        Context $context,
        OrderFactory $orderFactory,
        Monolog $logger,
        ScopeConfigInterface $config
    ) {
        $this->orderFactory = $orderFactory;
        $this->logger = $logger;
        parent::__construct($context, $config);
    }

    public function execute()
    {
        try {
            if (!$this->config->getValue(self::ENABLE)) {
                throw new \LogicException('API endpoint has been disabled');
            }
            $orderFactory = $this->orderFactory->create();
            $pageToken = $this->getRequest()->getParam('page_token');
            $postData = $this->getRequest()->getPost();
            $countOfOrders = $this->config->getValue(self::COUNT_ORDERS_PER_REQUEST);
            if ($countOfOrders > self::ORDERS_PER_REQUEST_MAXIMUM) {
                $countOfOrders = self::ORDERS_PER_REQUEST_MAXIMUM;
            }
            if ($countOfOrders < self::ORDERS_PER_REQUEST_MINIMUM) {
                $countOfOrders = self::ORDERS_PER_REQUEST_MINIMUM;
            }
            $response = $orderFactory->getOrders($pageToken, $countOfOrders, $postData);
            $this->responseEvents = $response;
        } catch (\Exception $exception) {
            print $exception;

            $this->logger->addCritical($exception->getMessage());
            $this->responseEvents = [];
            $this->status = 500;
            $this->error = $exception;
            parent::execute();
        } finally {
            $this->getResponse()->representJson(json_encode($this->responseEvents));
            $this->getResponse()->setStatusCode($this->status);
            parent::execute();
        }
    }
}
