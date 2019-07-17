<?php

namespace Gudtech\RetailOps\Controller\Order;

use Gudtech\RetailOps\Controller\RetailOps;
use Gudtech\RetailOps\Model\AcknowledgeFactory;
use Gudtech\RetailOps\Model\Logger\Monolog;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;

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
    protected $areaName = self::BEFOREPULL . self::SERVICENAME;

    /**
     * @var \Gudtech\RetailOps\Model\Acknowledge
     */
    protected $orderFactory;

    /**
     * @var null|string|array
     */
    protected $responseEvents;

    /**
     * @var Monolog
     */
    protected $logger;

    /**
     * @var string|int
     */
    protected $status = 200;

    /**
     * Acknowledge constructor.
     *
     * @param Context $context
     * @param AcknowledgeFactory $orderFactory
     * @param Monolog $logger
     * @param ScopeConfigInterface $config
     */
    public function __construct(
        Context $context,
        AcknowledgeFactory $orderFactory,
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
            $postData = $this->getRequest()->getPost();
            $orderFactrory = $this->orderFactory->create();
            $response = $orderFactrory->setOrderRefs($postData);
            $this->responseEvents = $response;
        } catch (\Exception $exception) {
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
