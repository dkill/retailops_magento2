<?php

namespace Gudtech\RetailOps\Service\CreditMemo;

use Gudtech\RetailOps\Model\Api\Traits\FullFilter;
use LogicException;
use Gudtech\RetailOps\Api\Services\CreditMemo\CreditMemoHelperInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\CreditmemoRepositoryInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Controller\Adminhtml\Order\CreditmemoLoader;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Config as OrderConfig;
use Magento\Sales\Model\Order\Email\Sender\CreditmemoSender;
use Magento\Sales\Model\Order\OrderStateResolverInterface;
use Magento\Sales\Model\Order\CreditmemoFactory;
use Magento\Sales\Model\Order\RefundAdapterInterface;

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
    private $refundCustomerbalanceReturn = 0;

    /**
     * @var float
     */
    protected $adjustmentNegative = 0;

    /**
     * @var float
     */
    protected $shippingAmount = 0;

    /**
     * @var int
     */
    private $sendEmail = 1;

    /**
     * @var CreditmemoFactory
     */
    protected $creditmemoFactory;

    /**
     * @var CreditmemoSender
     */
    protected $creditmemoSender;

    /**
     * @var OrderRepository
     */
    protected $orderRepository;

    /**
     * @var RefundAdapterInterface
     */
    private $refundAdapter;

    /**
     * @var OrderStateResolverInterface
     */
    private $orderStateResolver;

    /**
     * @var CreditmemoRepositoryInterface
     */
    private $creditmemoRepository;

    /**
     * CreditMemoHelper constructor.
     *
     * @param CreditmemoFactory $creditmemoFactory
     * @param CreditmemoSender $creditmemoSender
     * @param OrderRepository $invoiceRepository
     */
    public function __construct(
        CreditmemoFactory $creditmemoFactory,
        CreditmemoSender $creditmemoSender,
        OrderRepositoryInterface $orderRepository,
        CreditmemoRepositoryInterface $creditmemoRepository,
        OrderStateResolverInterface $orderStateResolver,
        OrderConfig $config,
        RefundAdapterInterface $refundAdapter
    ) {
        $this->creditmemoFactory = $creditmemoFactory;
        $this->creditmemoSender = $creditmemoSender;
        $this->orderRepository = $orderRepository;
        $this->creditmemoRepository = $creditmemoRepository;
        $this->orderStateResolver = $orderStateResolver;
        $this->config = $config;
        $this->refundAdapter = $refundAdapter;
    }

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
     * @return Order\Creditmemo
     */
    public function create(OrderInterface $order, array $items)
    {
        $data = [
            'qtys' => $items,
        ];

        $creditmemo = $this->creditmemoFactory->createByOrder($order, $data);

        if ($creditmemo) {

            $creditmemo->setCustomerBalanceRefundFlag($this->getRefundCustomerbalanceReturnEnable($order, $items));
            $creditmemo->setBsCustomerBalTotalRefunded($this->getRefundCustomerbalanceReturn($order, $items));
            $creditmemo->setCustomerBalTotalRefunded($this->getRefundCustomerbalanceReturn($order, $items));
            $creditmemo->setBaseCustomerBalanceRefunded($this->getRefundCustomerbalanceReturn($order, $items));
            $creditmemo->setCustomerBalanceRefunded($this->getRefundCustomerbalanceReturn($order, $items));
            $creditmemo->setBaseCustomerBalanceReturnMax($this->getRefundCustomerbalanceReturn($order, $items));

            if (!$creditmemo->isValidGrandTotal()) {
                throw new LocalizedException(
                    __('The credit memo\'s total must be positive.')
                );
            }

            $creditmemo->setState(\Magento\Sales\Model\Order\Creditmemo::STATE_REFUNDED);
            $order->setCustomerNoteNotify($this->getSendEmail($order));
            $order = $this->refundAdapter->refund($creditmemo, $order);
            $orderState = $this->orderStateResolver->getStateForOrder($order, []);
            $order->setState($orderState);
            $statuses = $this->config->getStateStatuses($orderState, false);
            $status = in_array($order->getStatus(), $statuses, true)
                ? $order->getStatus()
                : $this->config->getStateDefaultStatus($orderState);
            $order->setStatus($status);

            $creditmemo = $this->creditmemoRepository->save($creditmemo);
            $order = $this->orderRepository->save($order);

            if ($this->getSendEmail($order)) {
                $this->creditmemoSender->send($creditmemo);
            }
        }

        return $creditmemo;
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
        $prepare['send_email'] = $this->getSendEmail($order);
        $prepare['shipping_amount'] = $this->getShippingAmount($order, $items);
        $prepare['adjustment_positive'] = $this->getAdjustmentPositive($order, $items);
        $prepare['adjustment_negative'] = $this->getAdjustmentNegative($order, $items);
        $prepare['customer_balance_refund_flag'] = $this->getRefundCustomerbalanceReturnEnable($order, $items);
        $prepare['customer_balance_amount'] = $this->getRefundCustomerbalanceReturn($order, $items);

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

    /**
     * @param OrderInterface $order
     * @param array $items
     * @return int
     */
    public function getRefundCustomerbalanceReturnEnable(OrderInterface $order, array $items)
    {
        return $this->refundCustomerbalanceReturnEnable;
    }

    /**
     * @param OrderInterface $order
     * @param array $items
     * @return float
     */
    public function getRefundCustomerbalanceReturn(OrderInterface $order, array $items)
    {
        return $this->refundCustomerbalanceReturn;
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

    /**
     * @param OrderInterface $order
     * @return \Magento\Framework\Phrase
     */
    public function getCommentText(OrderInterface $order)
    {
        return __('Credit memo created by RetailOps');
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

    /**
     * @param float $amount
     */
    public function setAdjustmentPositive($amount)
    {
        $this->adjustmentNegative = $amount;
    }

    /**
     * @param boolean $isEnabled
     */
    public function setRefundCustomerbalanceReturnEnable($isEnabled)
    {
        $this->refundCustomerbalanceReturnEnable = $isEnabled;
    }

    /**
     * @param float $amount
     */
    public function setRefundCustomerbalanceReturnAmount($amount)
    {
        $this->refundCustomerbalanceReturn = $amount;
    }

    /**
     * @param float $amount
     */
    public function setAdjustmentNegative($amount)
    {
        $this->adjustmentNegative = $amount;
    }

    /**
     * @param float $amount
     */
    public function setShippingAmount($amount)
    {
        $this->shippingAmount = $amount;
    }

    /**
     * @param boolean $sendMail
     */
    public function setSendEmail($sendMail)
    {
        $this->sendMail = $sendMail;
    }

    /**
     * @param OrderInterface $order
     * @return mixed
     */
    public function getSendEmail(OrderInterface $order)
    {
        return $this->sendEmail;
    }
}
