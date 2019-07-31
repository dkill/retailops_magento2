<?php

namespace Gudtech\RetailOps\Controller\Order;

use \Gudtech\RetailOps\Controller\AbstractController;
use Gudtech\RetailOps\Model\Logger\Monolog;
use Gudtech\RetailOps\Model\Order\UpdateFactory;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * Update controller class action.
 *
 */
class Update extends AbstractController
{
    const SERVICENAME = 'order_update';
    const ENABLE = 'retailops/retailops_feed/order_update';

    protected $events = [];

    protected $responseEvents = [];

    protected $status = 'success';

    /**
     * Update constructor.
     *
     * @param Context $context
     * @param UpdateFactory $orderFactory
     * @param Monolog $logger
     * @param ScopeConfigInterface $config
     */
    public function __construct(
        Context $context,
        UpdateFactory $orderFactory,
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
            $areaName = "retailops_before_pull_" . self::SERVICENAME;
            if (!$this->config->getValue(self::ENABLE)) {
                throw new \LogicException('API endpoint has been disabled');
            }
            $postData = $this->getRequest()->getPost();
            $orderFactory = $this->orderFactory->create();
            $response = $orderFactory->updateOrder($postData);
            $this->_eventManager->dispatch($areaName, [
                'response' => $response,
                'request' => $this->getRequest(),
            ]);
            $this->responseEvents = $response;
        } catch (\Exception $exception) {
            $event = [
                'event_type' => 'error',
                'code' => $exception->getCode(),
                'message' => $exception->getMessage(),
                'diagnostic_data' => 'string',
                'associations' => $this->association,
            ];

            $this->events[] = $event;
            $this->status = 'error';

        } finally {
            $this->responseEvents['events'] = [];
            foreach ($this->events as $event) {
                $this->responseEvents['events'][] = $event;
            }
            $this->_eventManager->dispatch($areaName, [
                'request' => $this->getRequest(),
                'response' => $this->responseEvents
            ]);
            $this->getResponse()->representJson(json_encode($this->responseEvents));
            $this->getResponse()->setStatusCode('200');
            return $this->getResponse();
        }
    }
}
