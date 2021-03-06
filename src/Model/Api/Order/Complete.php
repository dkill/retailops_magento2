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

        // Try to cancel the order
        $this->cancel($this->getOrder($orderId));

        // Refund unshipped items
        $this->removeAllUnshippedItems($this->getOrder($orderId));

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

    /**
     * Creates the credit memo if needed.
     *
     * @param OrderInterface $order
     * @param array $items
     * @param float $refundAmount
     */
    public function createCreditMemoIfNeed(OrderInterface $order, array $items, $refundAmount)
    {
        if (count($items) > 0) {

            if (($order->getCustomerBalanceAmount() - $order->getCustomerBalanceRefunded()) > 0) {
                if ($refundAmount > ($order->getCustomerBalanceAmount() - $order->getCustomerBalanceRefunded())) {
                    $refundAmount = $order->getCustomerBalanceAmount() - $order->getCustomerBalanceRefunded();
                }

                $this->creditMemoHelper->setRefundCustomerbalanceReturnEnable(1);
                $this->creditMemoHelper->setRefundCustomerbalanceReturnAmount($refundAmount);
            }

            $this->creditMemoHelper->create($order, $items);
        }
    }

    /**
     * Refund all unshipped items for the order.
     *
     * @param OrderInterface $order
     */
    public function removeAllUnshippedItems(OrderInterface $order)
    {
        $items = $order->getItems();
        $refundAmount = 0;
        $refundItems = [];
        foreach ($items as $item) {
            if ($item->getParentItem()) {
                continue;
            }
            $quantity = $this->getRefundQuantity($item);
            if ($quantity > 0) {
                $refundItems[$item->getId()] = $quantity;
                $refundAmount = $refundAmount + $item->getRowTotalInclTax();
            }
        }

        $this->createCreditMemoIfNeed($order, $refundItems, $refundAmount);
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
