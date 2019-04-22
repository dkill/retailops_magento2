<?php

namespace RetailOps\Api\Controller\Frontend\Order;

use Magento\Framework\App\ObjectManager;
use \RetailOps\Api\Controller\RetailOps;

class Acknowledge extends RetailOps
{
    const SERVICENAME = 'order_acknowledge';
    const ENABLE = 'retailops/RetailOps_feed/order_acknowledge';
    /**
     * @var string
     */
    protected $areaName = self::BEFOREPULL.self::SERVICENAME;

    /**
     * @var \RetailOps\Api\Model\Acknowledge
     */
    protected $orderFactory;

    /**
     * @var null|string|array
     */
    protected $response;

    /**
     * @var \RetailOps\Api\Logger\Logger
     */
    protected $logger;

    /**
     * @var string|int
     */
    protected $status = 200;


    public function execute()
    {
        try {
            $scopeConfig = $this->_objectManager->get('\Magento\Framework\App\Config\ScopeConfigInterface');
            if (!$scopeConfig->getValue(self::ENABLE)) {
                throw new \LogicException('This feed disable');
            }
            $postData = $this->getRequest()->getPost();
            $orderFactrory = $this->orderFactory->create();
            $response = $orderFactrory->setOrderRefs($postData);
            $this->response = $response;
        } catch (\Exception $e) {
            $this->logger->addCritical($e->getMessage());
            $this->response = (object)null;
            $this->status = 500;
            $this->error = $e;
            parent::execute();
        } finally {
            $this->getResponse()->representJson(json_encode($this->response));
            $this->getResponse()->setStatusCode($this->status);
            parent::execute();
        }
    }

    public function __construct(
        \RetailOps\Api\Model\AcknowledgeFactory $orderFactory,
        \Magento\Framework\App\Action\Context $context
    ) {
        $this->orderFactory = $orderFactory;
        parent::__construct($context);
        $this->logger = $this->_objectManager->get('\RetailOps\Api\Logger\Logger');
    }
}
