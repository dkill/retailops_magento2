<?php

namespace Gudtech\RetailOps\Model\Shipment;

use Exception;
use Gudtech\RetailOps\Api\Services\CreditMemo\CreditMemoHelperInterface;
use Gudtech\RetailOps\Api\Shipment\ShipmentInterface;
use Gudtech\RetailOps\Model\Api\Traits\Filter;
use Gudtech\RetailOps\Service\InvoiceHelper;
use Gudtech\RetailOps\Service\ItemsManager;
use Gudtech\RetailOps\Service\ItemsManagerFactory;
use Gudtech\RetailOps\Service\OrderCheck;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Sales\Model\Service\OrderService;

/**
 * Shipment submit class.
 *
 */
class ShipmentSubmit
{
    use Filter;

    /**
     * @var ShipmentInterface
     */
    protected $shipment;

    /**
     * @var OrderCheck
     */
    protected $orderCheck;

    /**
     * @var ItemsManager
     */
    protected $itemsManager;

    /**
     * @var array
     */
    protected $events = [];

    /**
     * @var array
     */
    protected $response;

    /**
     * @var InvoiceHelper
     */
    protected $invoiceHelper;

    /**
     * @var CreditMemoHelperInterface
     */
    protected $creditMemoHelper;

    /**
     * @var OrderManagementInterface
     */
    protected $orderManager;

    /**
     * ShipmentSubmit constructor.
     * @param ShipmentInterface $shipment
     * @param OrderCheck $orderCheck
     */
    public function __construct(
        ShipmentInterface $shipment,
        OrderCheck $orderCheck,
        ItemsManagerFactory $itemsManagerFactory,
        OrderService $orderManagement,
        InvoiceHelper $invoiceHelper
    ) {
        $this->shipment = $shipment;
        $this->orderCheck = $orderCheck;
        $this->itemsManager = $itemsManagerFactory->create();
        $this->invoiceHelper = $invoiceHelper;
        $this->orderManager = $orderManagement;
    }

    public function updateOrder(array $postData)
    {
        try {
            $orderId = $this->getOrderIdByOrderIncrementId($postData['channel_order_refnum']);
            $order = $this->getOrder($orderId);

            $this->shipment->setOrder($order);
            $this->shipment->setUnShippedItems($postData);
            $this->shipment->setTrackingAndShipmentItems($postData);

            //for synchronize with complete block, add shipments key
            if (array_key_exists('shipment', $postData) && !array_key_exists('shipments', $postData)) {
                $postData['shipments'][] = $postData['shipment'];
                unset($postData['shipment']);
            }

            $this->shipment->registerShipment($postData);
        } catch (Exception $e) {
            $this->setEventsInfo($e);
        } finally {
            $this->response['events'] = $this->events;
            return $this->response;
        }
    }

    /**
     * @param  Exception $e
     */
    protected function setEventsInfo($e)
    {
        $event = [];
        $event['event_type'] = 'error';
        $event['code'] = (string)$e->getCode();
        $event['message'] = $e->getMessage();
        $event['diagnostic_data'] = $e->getFile();
        $event['associations'] = [];
        if (isset($orderId)) {
            $event['associations'][] = [
                'identifier_type' => 'order_refnum',
                'identifier' => (string)$orderId];

        }
        $this->events[] = $event;
    }

    /**
     * @param int|string $orderId
     * @return OrderInterface
     */
    protected function getOrder($orderId)
    {
        return $this->orderCheck->getOrder((int)$orderId);
    }
}
