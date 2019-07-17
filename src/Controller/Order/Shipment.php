<?php

namespace Gudtech\RetailOps\Controller\Order;

use Gudtech\RetailOps\Controller\RetailOps;
use Gudtech\RetailOps\Model\Logger\Monolog;
use Gudtech\RetailOps\Model\Shipment\ShipmentSubmit;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;

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
     * @var ShipmentSubmit
     */
    protected $shipmentSubmit;

    /**
     * @var string
     */
    protected $statusRetOps = 'success';

    private $responseEvents = [];

    /**
     * @var array|null
     */
    protected $events=[];

    public function __construct(
        Context $context,
        ShipmentSubmit $shipmentSubmit,
        Monolog $logger,
        ScopeConfigInterface $config
    ) {
        $this->shipmentSubmit = $shipmentSubmit;
        $this->logger = $logger;
        parent::__construct($context, $config);
    }

    public function execute()
    {
        try {
            if (!$this->config->getValue(self::ENABLE)) {
                throw new \LogicException('API endpoint has been disabled');
            }
            $postData = (array)$this->getRequest()->getPost();
            $response = $this->shipmentSubmit->updateOrder($postData);
            $this->responseEvents = $response;
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
            if (!array_key_exists('events', $this->responseEvents)) {
                $this->responseEvents['events'] = [];
            }
//            $this->response['status'] = $this->response['status'] ?? $this->statusRetOps;
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
