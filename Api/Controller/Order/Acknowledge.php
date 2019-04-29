<?php

namespace RetailOps\Api\Controller\Order;

use Magento\Framework\App\ObjectManager;
use \RetailOps\Api\Controller\RetailOps;

/**
 * Acknowledge controller action class.
 *
 */
class Acknowledge extends RetailOps
{
    const SERVICENAME = 'order_acknowledge';
    const ENABLE = 'retailops/retailops_feed/order_acknowledge';

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
     * @var \RetailOps\Api\Model\Logger\Monolog
     */
    protected $logger;

    /**
     * @var string|int
     */
    protected $status = 200;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \RetailOps\Api\Model\AcknowledgeFactory $orderFactory,
        \RetailOps\Api\Model\Logger\Monolog $logger
    ) {
        $this->orderFactory = $orderFactory;
        $this->logger = $logger;
        parent::__construct($context);
    }

    public function execute()
    {
        try {
            $scopeConfig = $this->_objectManager->get(\Magento\Framework\App\Config\ScopeConfigInterface::class);
            if (!$scopeConfig->getValue(self::ENABLE)) {
                throw new \LogicException('API endpoint has been disabled');
            }
            $postData = $this->getRequest()->getPost();
            $orderFactrory = $this->orderFactory->create();
            $response = $orderFactrory->setOrderRefs($postData);
            $this->response = $response;
        } catch (\Exception $exception) {
            $this->logger->addCritical($exception->getMessage());
            $this->response = (object)null;
            $this->status = 500;
            $this->error = $exception;
            parent::execute();
        } finally {
            $this->getResponse()->representJson(json_encode($this->response));
            $this->getResponse()->setStatusCode($this->status);
            parent::execute();
        }
    }
}
