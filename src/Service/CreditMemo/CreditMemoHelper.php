<?php

namespace Gudtech\RetailOps\Service\CreditMemo;

use Gudtech\RetailOps\Model\Api\Traits\FullFilter;
use LogicException;
use Magento\Framework\App\ObjectManager;
use Gudtech\RetailOps\Api\Services\CreditMemo\CreditMemoHelperInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\CreditmemoManagementInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Controller\Adminhtml\Order\CreditmemoLoader;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\CreditmemoSender;
use Magento\Sales\Model\Order\InvoiceRepository;

/**
 * Credit memo helper class.
 *
 */
class CreditMemoHelper implements CreditMemoHelperInterface
{
    use FullFilter;

    /**
     * @var float
     */
    protected $adjustmentPositive = 0;

    /**
     * @var float
     */
    protected $refundCustomerbalanceReturnEnable = 0;

    /**
     * @var float
     */
    protected $adjustmentNegative = 0;

    /**
     * @var float
     */
    protected $shippingAmount = 0;

    /**
     * @var CreditmemoLoader
     */
    protected $creditmemoLoader;

    /**
     * @var CreditmemoSender
     */
    protected $creditmemoSender;

    /**
     * @var InvoiceRepository
     */
    protected $invoiceRepository;

    /**
     * @var ObjectManager
     */
    protected $_objectManager;

    /**
     * @param OrderItemInterface $orderItem
     * @param $value
     * @return float
     */
    public function getQuantity(OrderItemInterface $orderItem, $value)
    {
        $value = (float)$value;
        if ($orderItem->getParentItem()) {
            $orderItemForCalc = $orderItem->getParentItem();
        } else {
            $orderItemForCalc = $orderItem;
        }
        $delta = (float)$orderItemForCalc->getQtyOrdered()
                    - (float)$orderItemForCalc->getQtyInvoiced()
                    - (float)$orderItemForCalc->getQtyRefunded()
                    - (float)$orderItemForCalc->getQtyCanceled();
        if ($delta >= $value) {
            return 0;
        }

        $qtyCreditMemo = $value - $delta;
        if ($qtyCreditMemo < 0 || $orderItemForCalc->getQtyOrdered() < $qtyCreditMemo) {
            throw new LogicException(
                'Qty of creditmemo more than quantity of invoice, item:'.$orderItemForCalc->getId()
            );
        }
        return $qtyCreditMemo;
    }

    /**
     * check, if we need create credit memo for product
     * @param Order $order
     * @param array ['id'=>'quantity'] $items
     * @return array
     */
    public function needCreditMemo(Order $order, $items = [])
    {
        /**
         * @var OrderItemInterface[] $itemsOrder
         */
        $itemsOrder = $order->getItems();
        $creditMemoItems = [];
        foreach ($items as $key => $value) {
            foreach ($itemsOrder as $itemOrder) {
                if ((string)$itemOrder->getId() === (string)$key) {
                    $quantity = $this->getCreditMemoQuantity($itemOrder, $value);
                    if ($quantity>0) {
                        $creditMemoItems[$key] = $quantity;
                    }
                }
            }
        }
        return $creditMemoItems;
    }

    public function getCreditMemoQuantity($itemOrder, $value)
    {
        return $this->getQuantity($itemOrder, $value);
    }

    /**
     * @param OrderInterface $order
     * @param array $items
     * @return boolean
     */
    public function create(OrderInterface $order, array $items)
    {
        $this->creditmemoLoader->setOrderId($order->getId());
        $this->creditmemoLoader->setCreditmemo($this->getPrepareCreditmemoData($order, $items));
        $invoice = $this->getInvoice($order, $items);
        if ($invoice) {
            $this->creditmemoLoader->setInvoiceId($invoice->getId());
        }

        $creditmemo = $this->creditmemoLoader->load();
        if ($creditmemo) {
            if (!$creditmemo->isValidGrandTotal()) {
                throw new LocalizedException(
                    __('The credit memo\'s total must be positive.')
                );
            }

            /**
             * @var CreditmemoManagementInterface $creditmemoManagement
             */
            $creditmemoManagement = $this->_objectManager->create(
                CreditmemoManagementInterface::class
            );
            /**
             * $creditmemo, offline/online, send_email
             */
            $creditmemoManagement->refund($creditmemo, $this->isOfflineRefund($order), 0);
        }
    }

    /**
     * @param OrderInterface $order
     * @param array $items
     * @return
     */
    public function getInvoice(OrderInterface $order, $items)
    {
        $filter = $this->createFilter('order_id', 'eq', $order->getId());
        $this->addFilter('invoices', $filter);
        $this->addFilterGroups();
        $invoices = $this->invoiceRepository->getList($this->searchCriteria);
        foreach ($invoices as $invoice) {
            //return first invoice
            return $invoice;
        }

        return null;
    }

    /**
     * @param $order
     * @param $items
     * @return array
     */
    public function getPrepareCreditmemoData(OrderInterface $order, array $items)
    {
        $prepare = [];
        $convertItems = [];
        foreach ($items as $id => $quantity) {
            $convertItems[$id] = ['qty'=>(float)$quantity];
        }
        $prepare['items'] = $convertItems;
        $prepare['do_offline'] = $this->setDoOffline($order, $items);
        $prepare['comment_text'] = $this->getCommentText($order);
        $prepare['shipping_amount'] = $this->getShippingAmount($order, $items);
        $prepare['adjustment_positive'] = $this->getAdjustmentPositive($order, $items);
        $prepare['adjustment_negative'] = $this->getAdjustmentNegative($order, $items);
        $prepare['refund_customerbalance_return_enable'] = $this->getRefundCustomerbalanceReturnEnable($order, $items);
        return $prepare;
    }

    /**
     * @param OrderInterface $order
     * @param array $items
     * @return int
     */
    public function getAdjustmentPositive(OrderInterface $order, array $items)
    {
        return $this->adjustmentPositive;
    }

    public function getRefundCustomerbalanceReturnEnable(OrderInterface $order, array $items)
    {
        return $this->refundCustomerbalanceReturnEnable;
    }

    /**
     * @param OrderInterface $order
     * @param array $items
     * @return int
     */
    public function getAdjustmentNegative(OrderInterface $order, array $items)
    {
        return $this->adjustmentNegative;
    }

    /**
     * @param OrderInterface $order
     * @param array $items
     * @return int
     */
    public function getShippingAmount(OrderInterface $order, array $items)
    {
        return $this->shippingAmount;
    }

    public function getCommentText(OrderInterface $order)
    {
        return __('Create for RetailOps response');
    }

    /**
     * @param OrderInterface $order
     * @param  array $items
     * @return bool
     */
    public function setDoOffline(OrderInterface $order, array $items)
    {
        return $this->isOfflineRefund($order);
    }

    /**
     * @param OrderInterface $order
     * @return bool
     */
    public function isOfflineRefund(OrderInterface $order)
    {
        $payment = $order->getPayment();
        if ($payment && $payment->getBaseAmountPaidOnline() > 0) {
            return 0;
        }
        return 1;
    }

    public function __construct(
        CreditmemoLoader $creditmemoLoader,
        CreditmemoSender $creditmemoSender,
        InvoiceRepository $invoiceRepository
    ) {
        $this->creditmemoLoader = $creditmemoLoader;
        $this->_objectManager = ObjectManager::getInstance();
        $this->creditmemoSender = $creditmemoSender;
        $this->invoiceRepository = $invoiceRepository;
    }

    public function setAdjustmentPositive($amount)
    {
        $this->adjustmentNegative = $amount;
    }

    public function setRefundCustomerbalanceReturnEnable($amount)
    {
        $this->refundCustomerbalanceReturnEnable = $amount;
    }

    public function setAdjustmentNegative($amount)
    {
        $this->adjustmentNegative = $amount;
    }

    public function setShippingAmount($amount)
    {
        $this->shippingAmount = $amount;
    }
}
