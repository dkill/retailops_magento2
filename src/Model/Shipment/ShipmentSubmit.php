<?php

namespace Gudtech\RetailOps\Model\Shipment;

/**
 * Shipment submit class.
 *
 */
class ShipmentSubmit
{
    use \Gudtech\RetailOps\Model\Api\Traits\Filter;

    /**
     * @var \Gudtech\RetailOps\Api\Shipment\ShipmentInterface
     */
    protected $shipment;

    /**
     * @var \Gudtech\RetailOps\Service\OrderCheck
     */
    protected $orderCheck;

    /**
     * @var \Gudtech\RetailOps\Service\ItemsManager
     */
    protected $itemsManager;

    protected $events=[];

    protected $response;

    /**
     * @var \Gudtech\RetailOps\Service\InvoiceHelper
     */
    protected $invoiceHelper;

    /**
     * @var \Gudtech\RetailOps\Api\Services\CreditMemo\CreditMemoHelperInterface
     */
    protected $creditMemoHelper;

    /**
     * @var \Magento\Sales\Api\OrderManagementInterface
     */
    protected $orderManager;

    /**
     * ShipmentSubmit constructor.
     * @param \Gudtech\RetailOps\Api\Shipment\ShipmentInterface $shipment
     * @param \Gudtech\RetailOps\Service\OrderCheck $orderCheck
     */
    public function __construct(
        \Gudtech\RetailOps\Api\Shipment\ShipmentInterface $shipment,
        \Gudtech\RetailOps\Service\OrderCheck $orderCheck,
        \Gudtech\RetailOps\Service\ItemsManagerFactory $itemsManagerFactory,
        \Magento\Sales\Model\Service\OrderService $orderManagement,
        \Gudtech\RetailOps\Service\InvoiceHelper $invoiceHelper
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
            $orderId = $this->getOrderIdByIncrement($postData['channel_order_refnum']);
            $order = $this->getOrder($orderId);

            $this->shipment->setOrder($order);
            $this->shipment->setUnShippedItems($postData);
            $this->shipment->setTrackingAndShipmentItems($postData);

            //for synchronize with complete block, add shipments key
            if (array_key_exists('shipment', $postData) && !array_key_exists('shipments', $postData)) {
                $postData['shipments'][] = $postData['shipment'];
                unset($postData['shipment']);
            }

            if (array_key_exists('items', $this->shipment->getShippmentItems()) &&
                count($this->shipment->getShippmentItems()['items'])
            ) {
                //remove items, that already had invoice
                $needInvoiceItems = $this->itemsManager->removeInvoicedAndShippedItems(
                    $order,
                    $this->shipment->getShippmentItems()['items']
                );

                if ($needInvoiceItems) {
                    $this->itemsManager->canInvoiceItems($order, $needInvoiceItems);
                    $this->invoiceHelper->createInvoice($order, $needInvoiceItems);
                }
            }
            $this->shipment->registerShipment($postData);
        } catch (\Exception $e) {
            $this->setEventsInfo($e);
        } finally {
            $this->response['events'] = $this->events;
            return $this->response;
        }
    }

    /**
     * @param  \Exception $e
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
     */
    protected function getOrder($orderId)
    {
        return $this->orderCheck->getOrder((int)$orderId);
    }
}
