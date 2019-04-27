<?php

namespace RetailOps\Api\Controller\Frontend\Order;

use Magento\Framework\App\ObjectManager;
use \RetailOps\Api\Controller\RetailOps;

/**
 * Complete controller action class.
 *
 */
class Complete extends RetailOps
{
    const SERVICENAME = 'order_complete';
    const ENABLE = 'retailops/retailops_feed/order_complete';
    protected $events = [];
    protected $response = [];
    protected $statusRetOps = 'success';

    /**
     * @var \\RetailOps\Model\Order\Complete
     */
    protected $orderFactory;
    /**
     * @var string
     */
    protected $areaName = self::BEFOREPULL.self::SERVICENAME;

    /**
     * @return \Magento\Framework\App\ResponseInterface
     */
    public function execute()
    {
        $this->logger->addCritical(json_encode($this->_request->getParams()));

        try {
            $scopeConfig = $this->_objectManager->get(\Magento\Framework\App\Config\ScopeConfigInterface::class);
            if (!$scopeConfig->getValue(self::ENABLE)) {
                throw new \LogicException('API endpoint has been disabled');
            }
            $postData = (array)$this->getRequest()->getPost();
            /**
             * @var \RetailOps\Api\Model\Order\Complete
             */
            $orderFactrory = $this->orderFactory->create();
            $response = $orderFactrory->updateOrder($postData);
            $this->response = $response;
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
            $this->response['events'] = $this->response['events'] ?? [] ;
            $this->response['status'] = $this->statusRetOps;
            foreach ($this->events as $event) {
                $this->response['events'][] = $event;
            }
            $this->getResponse()->representJson(json_encode($this->response));
            $this->getResponse()->setStatusCode('200');
            parent::execute();
            return $this->getResponse();
        }
    }

    public function __construct(
        \RetailOps\Api\Model\Order\CompleteFactory $orderFactory,
        \Magento\Framework\App\Action\Context $context
    ) {
        $this->orderFactory = $orderFactory;
        parent::__construct($context);
        $this->logger = $this->_objectManager->get(\RetailOps\Api\Logger\Logger::class);
    }
}
