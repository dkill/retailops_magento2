<?php

namespace Gudtech\RetailOps\Controller\Order;

use Magento\Framework\App\ObjectManager;
use \Gudtech\RetailOps\Controller\RetailOps;

/**
 * Return order controller class action.
 *
 */
class ReturnOrder extends RetailOps
{
    const ENABLE = 'retailops/retailops_feed/order_return';

    /**
     * @var \Gudtech\RetailOps\Model\Order\OrderReturn
     */
    protected $orderReturn;

    protected $events;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Gudtech\RetailOps\Model\Order\OrderReturn $orderReturn,
        \Gudtech\RetailOps\Model\Logger\Monolog $logger
    ) {
        $this->orderReturn = $orderReturn;
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
            $postData = (array)$this->getRequest()->getPost();
            $response = $this->orderReturn->returnOrder($postData);
            $this->response = $response;
        } catch (\Exception $e) {
            $event = [
                'event_type' => 'error',
                'code' => $e->getCode(),
                'message' => $e->getMessage(),
                'diagnostic_data' => 'string',
                'associations' => $this->association,
            ];
            $this->error = $e;
            $this->events[] = $event;
            $this->statusRetOps = 'error';

        } finally {
            if (!array_key_exists('events', $this->response)) {
                $this->response['events'] = [];
            }
//            $this->response['status'] = $this->response['status'] ?? $this->statusRetOps;
            foreach ($this->events as $event) {
                $this->response['events'][] = $event;
            }
            $this->getResponse()->representJson(json_encode($this->response));
            $this->getResponse()->setStatusCode('200');
            parent::execute();
            return $this->getResponse();
        }
    }
}
