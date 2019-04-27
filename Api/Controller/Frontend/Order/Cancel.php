<?php

namespace RetailOps\Api\Controller\Frontend\Order;

use Magento\Framework\App\ObjectManager;
use \RetailOps\Api\Controller\RetailOps;

/**
 * Cancel controller action class.
 *
 */
class Cancel extends RetailOps
{
    const SERVICENAME = 'order_cancel';
    const ENABLE = 'retailops/retailops_feed/order_cancel';

    /**
     * @var string
     */
    protected $areaName = self::BEFOREPULL. self::SERVICENAME;

    public function execute()
    {
        try {
            $scopeConfig = $this->_objectManager->get(\Magento\Framework\App\Config\ScopeConfigInterface::class);
            if (!$scopeConfig->getValue(self::ENABLE)) {
                throw new \LogicException('API endpoint has been disabled');
            }
            $postData = $this->getRequest()->getParams();
            $orderFactrory = $this->orderFactory->create();
            $response = $orderFactrory->cancelOrder($postData);
            $this->response = $response;
        } catch (\Exception $exception) {
            print $exception;
            exit;
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

    public function __construct(
        \RetailOps\Api\Model\Order\CancelFactory $orderFactory,
        \Magento\Framework\App\Action\Context $context
    ) {
        $this->orderFactory = $orderFactory;
        parent::__construct($context);
        $this->logger = $this->_objectManager->get(\RetailOps\Api\Logger\Logger::class);
    }
}
