<?php

namespace Gudtech\RetailOps\Controller\Order;

use Gudtech\RetailOps\Controller\AbstractController;
use Gudtech\RetailOps\Model\Logger\Monolog;
use Gudtech\RetailOps\Model\Order\CompleteFactory;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * Complete controller action class.
 *
 */
class Complete extends AbstractController
{
    const SERVICENAME = 'order_complete';
    const ENABLE = 'retailops/retailops_feed/order_complete';
    protected $events = [];
    protected $responseEvents = [];
    protected $statusRetOps = 'success';

    /**
     * @var \RetailOps\Model\Order\Complete
     */
    protected $orderFactory;

    /**
     * @var string
     */
    protected $areaName = self::BEFOREPULL . self::SERVICENAME;

    /**
     * Complete constructor.
     *
     * @param Context $context
     * @param CompleteFactory $orderFactory
     * @param Monolog $logger
     * @param ScopeConfigInterface $config
     */
    public function __construct(
        Context $context,
        CompleteFactory $orderFactory,
        Monolog $logger,
        ScopeConfigInterface $config
    ) {
        $this->orderFactory = $orderFactory;
        $this->logger = $logger;
        parent::__construct($context, $config);
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface
     */
    public function execute()
    {
        $this->logger->addCritical(json_encode($this->_request->getParams()));

        try {
            if (!$this->config->getValue(self::ENABLE)) {
                throw new \LogicException('API endpoint has been disabled');
            }
            $postData = (array)$this->getRequest()->getPost();
            /**
             * @var \Gudtech\RetailOps\Model\Order\Complete
             */
            $orderFactory = $this->orderFactory->create();
            $response = $orderFactory->updateOrder($postData);
            $this->responseEvents = $response;
        } catch (\Exception $exception) {
            print $exception;
            $event = [
                'event_type' => 'error',
                'code' => $exception->getCode(),
                'message' => $exception->getMessage(),
                'diagnostic_data' => 'string',
                'associations' => $this->association,
            ];
            $this->error = $exception;
            $this->events[] = $event;
            $this->statusRetOps = 'error';
            parent::execute();
        } finally {
            $this->responseEvents['events'] = $this->responseEvents['events'] ?? [];
            $this->responseEvents['status'] = $this->statusRetOps;
            foreach ($this->events as $event) {
                $this->responseEvents['events'][] = $event;
            }
            $this->getResponse()->representJson(json_encode($this->responseEvents));
            $this->getResponse()->setStatusCode('200');
            parent::execute();
            return $this->getResponse();
        }
    }
}
