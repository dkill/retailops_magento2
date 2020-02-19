<?php

namespace Gudtech\RetailOps\Model\Api\Order;

use Gudtech\RetailOps\Api\Services\CreditMemo\CreditMemoHelperInterface;
use Gudtech\RetailOps\Api\Shipment\ShipmentInterface;
use Gudtech\RetailOps\Model\Api\Traits\Filter;
use Gudtech\RetailOps\Model\Logger\Monolog;
use Gudtech\RetailOps\Service\InvoiceHelper;
use Gudtech\RetailOps\Service\ItemsManagerFactory;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order\Email\Sender\ShipmentSender;
use Magento\Sales\Model\Service\OrderService;
use Magento\Shipping\Controller\Adminhtml\Order\ShipmentLoader;
use Magento\Shipping\Model\Config;

/**
 * Complete order class.
 *
 */
class Complete
{
    use Filter;

    const COMPLETE = 'complete';

    /**
     * @var OrderRepositoryInterface
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
     * @var Monolog
     */
    protected $logger;

    /**
     * @var Config
     */
    protected $shippingConfig;

    /**
     * @var array
     */
    protected $unShippmentItems = [];

    /**
     * @var array
     */
    protected $shippmentItems = [];

    /**
     * @var ShipmentSender
     */
    protected $shipmentSender;

    /**
     * @var array
     */
    protected $tracking;

    /**
     * @var ShipmentLoader
     */
    protected $shipmentLoader;

    /**
     * @var array|null
     */
    protected $response;

    /**
     * @var ShipmentInterface
     */
    protected $shipment;

    /**
     * @var InvoiceHelper
     */
    protected $invoiceHelper;

    /**
     * @var CreditMemoHelperInterface
     */
    protected $creditMemoHelper;

    /**
     * @var OrderInterface
     */
    protected $order;

    /**
     * @var OrderManagementInterface
     */
    protected $orderManager;

    /**
     * Complete constructor.
     * @param OrderRepositoryInterface $orderRepository
     * @param Monolog $logger
     * @param ShipmentInterface $shipment
     * @param InvoiceHelper $invoiceHelper
     * @param CreditMemoHelperInterface $creditMemoHelper
     * @param ItemsManagerFactory $itemsManagerFactory
     * @param OrderService $orderManagement
     */
    public function __construct(
        OrderRepositoryInterface $orderRepository,
        Monolog $logger,
        ShipmentInterface $shipment,
        InvoiceHelper $invoiceHelper,
        CreditMemoHelperInterface $creditMemoHelper,
        ItemsManagerFactory $itemsManagerFactory,
        OrderService $orderManagement
    ) {
        $this->orderRepository = $orderRepository;
        $this->logger =  $logger;
        $this->shipment = $shipment;
        $this->invoiceHelper = $invoiceHelper;
        $this->creditMemoHelper = $creditMemoHelper;
        $this->itemsManager = $itemsManagerFactory->create();
        $this->orderManager = $orderManagement;
    }

    /**
     * @param array $postData
     * @return array
     */
    public function completeOrder($postData)
    {
        $this->response['status'] = 'success';

        if (!isset($postData['channel_order_refnum'])) {
            throw new \LogicException(__("Don't have any order refnum"));
        }

        $orderId = $this->getOrderIdByOrderIncrementId($postData['channel_order_refnum']);

        $shipment = $this->shipment;
        $shipment->setOrder($this->getOrder($orderId));
        $shipment->setUnShippedItems($postData);
        $shipment->setTrackingAndShipmentItems($postData);
        $unShipmentItems = $shipment->getUnShippmentItems();

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
        $this->removeAllUnshippedItems($this->getOrder($orderId, true));
        return $this->response;
    }

    public function getOrder($orderId, $reset = false)
    {
        if (is_object($this->order) && !$reset) {
            return $this->order;
        }
        /**
         * @var OrderInterface $order
         */
        $order = $this->orderRepository->get($orderId);
        if (!$order->getId()) {
            throw new \LogicException(sprintf('Don\'t have order with refnum %s', $orderId));
        }
        $this->order = $order;
        return $this->order;
    }

    public function createCreditMemoIfNeed(OrderInterface $order, array $items)
    {
        if (count($items) > 0) {
            $this->creditMemoHelper->create($order, $items);
        }
    }

    public function removeAllUnshippedItems(OrderInterface $order)
    {
        /**
         * @var OrderItemInterface[] $items
         */
        $items = $order->getItems();
        $refundedItems = [];
        foreach ($items as $item) {
            /**
             * @var OrderItemInterface $item
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
    protected function getRefundQuantity(OrderItemInterface $item)
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

    public function cancel($order)
    {
        $this->orderManager->cancel($order->getId());
    }
}
