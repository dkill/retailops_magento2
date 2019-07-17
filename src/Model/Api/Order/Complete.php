<?php

namespace Gudtech\RetailOps\Model\Api\Order;

/**
 * Complete order class.
 *
 */
class Complete
{
    use \Gudtech\RetailOps\Model\Api\Traits\Filter;

    const COMPLETE = 'complete';

    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var \Gudtech\RetailOps\Service\ItemsManager
     */
    protected $itemsManager;

    /**
     * @var array
     */
    protected $cancelItems = [];
    /**
     * @var \\Gudtech\RetailOps\Model\Logger\Monolog
     */
    protected $logger;

    /**
     * @var \Magento\Shipping\Model\Config
     */
    protected $shippingConfig;

    /**
     * @var array
     */
    protected $unShippmentItems = [];

    protected $shippmentItems = [];

    /**
     * @var \Magento\Sales\Model\Order\Email\Sender\ShipmentSender
     */
    protected $shipmentSender;

    /**
     * @var array
     */
    protected $tracking;

    /**
     * @var \Magento\Shipping\Controller\Adminhtml\Order\ShipmentLoader
     */
    protected $shipmentLoader;

    /**
     * @var array|null
     */
    protected $response;

    /**
     * @var \Gudtech\RetailOps\Api\Shipment\ShipmentInterface
     */
    protected $shipment;

    /**
     * @var \Gudtech\RetailOps\Service\InvoiceHelper
     */
    protected $invoiceHelper;

    /**
     * @var \Gudtech\RetailOps\Api\Services\CreditMemo\CreditMemoHelperInterface
     */
    protected $creditMemoHelper;

    /**
     * @var \Magento\Sales\Api\Data\OrderInterface
     */
    protected $order;

    /**
     * @var \Magento\Sales\Api\OrderManagementInterface
     */
    protected $orderManager;

    /**
     * @param array $postData
     */
    public function completeOrder($postData)
    {
        $this->response['status'] = 'success';

        if (!isset($postData['channel_order_refnum'])) {
            throw new \LogicException(__('Don\'t have any order refnum'));
        }

        $orderId = $this->getOrderIdByIncrement($postData['channel_order_refnum']);
        $shipment = $this->shipment;
        $shipment->setOrder($this->getOrder($orderId));
        //create invoice, with shipments items
        $shipment->setUnShippedItems($postData);
        $shipment->setTrackingAndShipmentItems($postData);
        $unShipmentItems = $shipment->getUnShippmentItems();
        //check, if we can do cancel for some items
        $needCreditMemoItems = $this->itemsManager->removeCancelItems($this->getOrder($orderId), $unShipmentItems);
        $this->createCreditMemoIfNeed($this->getOrder($orderId), $needCreditMemoItems);

        if (array_key_exists('items', $this->shipment->getShippmentItems()) &&
            count($this->shipment->getShippmentItems()['items'])
        ) {
            //remove items, that already had invoice
            $needInvoiceItems = $this->itemsManager->removeInvoicedAndShippedItems(
                $this->getOrder($orderId),
                $this->shipment->getShippmentItems()['items']
            );

            if (count($needInvoiceItems)) {
                $this->itemsManager->canInvoiceItems($this->getOrder($orderId), $needInvoiceItems);
                $this->invoiceHelper->createInvoice($this->getOrder($orderId), $needInvoiceItems);
            }
        }

        //all available items cancel
        $this->cancel($this->getOrder($orderId));
        $this->getOrder($orderId)->setStatus(self::COMPLETE);
        $shipment->registerShipment($postData);
        $this->removeAllUnShipedItems($this->getOrder($orderId, true));
        return $this->response;
    }

    public function getOrder($orderId, $reset = false)
    {
        if (is_object($this->order) && !$reset) {
            return $this->order;
        }
        /**
         * @var \Magento\Sales\Api\Data\OrderInterface $order
         */
        $order = $this->orderRepository->get($orderId);
        if (!$order->getId()) {
            throw new \LogicException(sprintf('Don\'t have order with refnum %s', $orderId));
        }
        $this->order = $order;
        return $this->order;
    }

    public function createCreditMemoIfNeed(\Magento\Sales\Api\Data\OrderInterface $order, array $items)
    {
        if (count($items) > 0) {
            $this->creditMemoHelper->create($order, $items);
        }
    }

    public function removeAllUnShipedItems(\Magento\Sales\Api\Data\OrderInterface $order)
    {
        /**
         * @var \Magento\Sales\Api\Data\OrderItemInterface[] $items
         */
        $items = $order->getItems();
        $refundedItems = [];
        foreach ($items as $item) {
            /**
             * @var \Magento\Sales\Api\Data\OrderItemInterface $item
             */
            if ($item->getParentItem()) {
                continue;
            }
            $quantity = $this->getRefundQuantity($item);
            if ($quantity > 0) {
                $refundedItems[$item->getId()] = $quantity;
            }
        }
        $this->createCreditMemoIfNeed($order, $refundedItems);
    }

    /**
     * @param $item
     * @return float
     */
    protected function getRefundQuantity(\Magento\Sales\Api\Data\OrderItemInterface $item)
    {
        if ($item->getParentItem()) {
            $item = $item->getParentItem();
        }
        $qty = $item->getQtyOrdered() - $item->getQtyRefunded() - $item->getQtyCanceled() - $item->getQtyShipped();
        return (float)$qty;
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
        if (isset($orderId)) {
            $event['associations'] = [
                'identifier_type' => 'order_refnum',
                'identifier' => (string)$orderId];

        }
        $this->events[] = $event;
    }

    /**
     * Complete constructor.
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     * @param \Gudtech\RetailOps\Model\Logger\Monolog $logger
     * @param \Gudtech\RetailOps\Api\Shipment\ShipmentInterface
     * @param \Gudtech\RetailOps\Service\InvoiceHelper $invoiceHelper
     * @param \Gudtech\RetailOps\Api\Services\CreditMemo\CreditMemoHelperInterface $creditMemoHelper
     * @param \Magento\Sales\Model\Service\OrderService $orderManagement
     */
    public function __construct(
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Gudtech\RetailOps\Model\Logger\Monolog $logger,
        \Gudtech\RetailOps\Api\Shipment\ShipmentInterface $shipment,
        \Gudtech\RetailOps\Service\InvoiceHelper $invoiceHelper,
        \Gudtech\RetailOps\Api\Services\CreditMemo\CreditMemoHelperInterface $creditMemoHelper,
        \Gudtech\RetailOps\Service\ItemsManagerFactory $itemsManagerFactory,
        \Magento\Sales\Model\Service\OrderService $orderManagement
    ) {
        $this->orderRepository = $orderRepository;
        $this->logger =  $logger;
        $this->shipment = $shipment;
        $this->invoiceHelper = $invoiceHelper;
        $this->creditMemoHelper = $creditMemoHelper;
        $this->itemsManager = $itemsManagerFactory->create();
        $this->orderManager = $orderManagement;
    }

    public function cancel($order)
    {
        $this->orderManager->cancel($order->getId());
    }
}
