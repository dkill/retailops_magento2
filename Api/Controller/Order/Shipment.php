<?php

namespace RetailOps\Api\Controller\Order;

use \RetailOps\Api\Controller\RetailOps;

/**
 * Shipment controller class action.
 *
 */
class Shipment extends RetailOps
{
    const SERVICENAME = 'shipment_submit';
    const COUNT_ORDERS_PER_REQUEST = 50;
    const ENABLE = 'retailops/retailops_feed/order_shipment_submit';

    /**
     * @var string
     */
    protected $areaName = self::BEFOREPULL.self::SERVICENAME;

    /**
     * @var \RetailOps\Api\Model\Shipment\ShipmentSubmit
     */
    protected $shipmentSubmit;

    /**
     * @var string
     */
    protected $statusRetOps = 'success';

    /**
     * @var array|null
     */
    protected $events=[];

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \RetailOps\Api\Model\Shipment\ShipmentSubmit $shipmentSubmit,
        \RetailOps\Api\Logger\Logger $logger
    ) {
        $this->shipmentSubmit = $shipmentSubmit;
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
            $response = $this->shipmentSubmit->updateOrder($postData);
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
            parent::execute();
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
