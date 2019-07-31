<?php

namespace Gudtech\RetailOps\Controller\Order;

use Gudtech\RetailOps\Controller\AbstractController;
use Gudtech\RetailOps\Model\Logger\Monolog;
use Gudtech\RetailOps\Model\Order\CancelFactory;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * Cancel controller action class.
 *
 */
class Cancel extends AbstractController
{
    const SERVICENAME = 'order_cancel';
    const ENABLE = 'retailops/retailops_feed/order_cancel';

    /**
     * @var string
     */
    protected $areaName = self::BEFOREPULL . self::SERVICENAME;

    /**
     * Cancel constructor.
     *
     * @param Context $context
     * @param CancelFactory $orderFactory
     * @param Monolog $logger
     * @param ScopeConfigInterface $config
     */
    public function __construct(
        Context $context,
        CancelFactory $orderFactory,
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
            $postData = $this->getRequest()->getParams();
            $orderFactrory = $this->orderFactory->create();
            $response = $orderFactrory->cancelOrder($postData);
            $this->responseEvents = $response;
        } catch (\Exception $exception) {
            print $exception;
            exit;
            $this->logger->addCritical($exception->getMessage());
            $this->responseEvents = (object)null;
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
