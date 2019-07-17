<?php

namespace RetailOps\Api\Controller\Order;

use Magento\Framework\App\ObjectManager;
use \RetailOps\Api\Controller\RetailOps;

/**
 * Update controller class action.
 *
 */
class Update extends RetailOps
{
    const SERVICENAME = 'order_update';
    const ENABLE = 'retailops/retailops_feed/order_update';

    protected $events = [];

    protected $response = [];

    protected $status = 'success';

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \RetailOps\Api\Model\Order\UpdateFactory $orderFactory,
        \RetailOps\Api\Model\Logger\Monolog $logger
    ) {
        $this->orderFactory = $orderFactory;
        $this->logger = $logger;
        parent::__construct($context);
    }

    public function execute()
    {
        try {
            $areaName = "retailops_before_pull_". self::SERVICENAME;
            $scopeConfig = $this->_objectManager->get(\Magento\Framework\App\Config\ScopeConfigInterface::class);
            if (!$scopeConfig->getValue(self::ENABLE)) {
                throw new \LogicException('API endpoint has been disabled');
            }
            $postData = $this->getRequest()->getPost();
            $orderFactory = $this->orderFactory->create();
            $response = $orderFactory->updateOrder($postData);
            $this->_eventManager->dispatch($areaName, [
                'response' => $response,
                'request' => $this->getRequest(),
            ]);
            $this->response = $response;
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
            $this->response['events'] = [];
            foreach ($this->events as $event) {
                $this->response['events'][] = $event;
            }
            $this->_eventManager->dispatch($areaName, [
                'request' => $this->getRequest(),
                'response' => $this->response
            ]);
            $this->getResponse()->representJson(json_encode($this->response));
            $this->getResponse()->setStatusCode('200');
            return $this->getResponse();
        }
    }
}
