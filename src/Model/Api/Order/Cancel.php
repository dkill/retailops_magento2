<?php

namespace Gudtech\RetailOps\Model\Api\Order;

use Magento\Sales\Model\Order\Email\Sender\OrderCommentSender;

/**
 * Cancel order class.
 *
 */
class Cancel
{
    use \Gudtech\RetailOps\Model\Api\Traits\Filter;

    const EMAIL_COMMENT_CANCELLED = 'Custom message to the customer. Needs to be changed.';

    /**
     * @var \Gudtech\RetailOps\Api\Services\CreditMemo\CreditMemoHelperInterface
     */
    protected $creditMemoHelper;
    protected $response;
    protected $status = 'success';
    protected $events = [];

    /**
     * @var \Gudtech\RetailOps\Model\Order\Status\History
     */
    protected $historyRetail;

    /**
     * @var OrderCommentSender
     */
    private $orderCommentSender;

    /**
     * Cancel constructor.
     *
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     * @param \Gudtech\RetailOps\Model\Logger\Monolog $logger
     * @param \Magento\Framework\Api\SearchCriteria $searchCriteria
     * @param \Magento\Framework\Api\FilterFactory $filter
     * @param \Magento\Framework\Api\Search\FilterGroupFactory $filterGroup
     */
    public function __construct(
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Gudtech\RetailOps\Model\Logger\Monolog $logger,
        \Magento\Framework\Api\SearchCriteria $searchCriteria,
        \Magento\Framework\Api\FilterFactory $filter,
        \Magento\Framework\Api\Search\FilterGroupFactory $filterGroup,
        \Gudtech\RetailOps\Model\Order\Status\History $historyRetail,
        \Gudtech\RetailOps\Api\Services\CreditMemo\CreditMemoHelperInterface $creditMemoHelper,
        OrderCommentSender $orderCommentSender
    ) {
        $this->orderRepository = $orderRepository;
        $this->logger = $logger;
        $this->searchCriteria = $searchCriteria;
        $this->filter = $filter;
        $this->filterGroup = $filterGroup;
        $this->historyRetail = $historyRetail;
        $this->creditMemoHelper = $creditMemoHelper;
        $this->orderCommentSender = $orderCommentSender;
    }

    public function cancel($orderInfo)
    {
        try {
            $orderId = $this->getOrderId($orderInfo);
            $order = $this->orderRepository->get($orderId);
            if ($order->getId()) {
                if ($order->canUnhold()) {
                      $order->unhold();
                }
                $this->cancelOrder($order);
            }
        } catch (\Exception $e) {
            $this->status = 'fail';
            $this->setEventsInfo($e);

        } finally {
            $response = [];
            $response['status'] = $this->status;
            $response['events'] = $this->events;
            $this->response = $response;
            return $this->response;
        }
    }

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
     * @param $orderId
     */
    protected function getOrderId($orderInfo)
    {
        if (isset($orderInfo['channel_order_refnum'])) {
            return $this->getOrderIdByOrderIncrementId($orderInfo['channel_order_refnum']);
        } else {
            $this->logger->addError('Invalid map', (array)$orderInfo);
            throw new \LogicException(__('invalid map'));

        }
    }

    /**
     * Cancels an order
     *
     * @param   \Magento\Sales\Api\Data\OrderInterface $order
     * @returns \bool
     * @throws  \Magento\Framework\Exception\LocalizedException
     */
    private function cancelOrder(\Magento\Sales\Api\Data\OrderInterface $order)
    {
        if ($order->canCancel()) {
            $order->cancel();
        } else {
            $this->allRefund($order);
        }

        # Add order comment
        $order->addStatusToHistory($order->getStatus(), "Cancelled by RetailOps", true);
        $this->orderRepository->save($order);

        # Send email to customer
        $this->orderCommentSender->send($order, true, self::EMAIL_COMMENT_CANCELLED);
    }

    /**
     * Refunds an order
     *
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     */
    private function allRefund(\Magento\Sales\Api\Data\OrderInterface $order)
    {
        $shippingRefund = $order->getShippingAmount() - $order->getShippingDiscountAmount();
        $this->creditMemoHelper->setShippingAmount($shippingRefund);

        if ($order->getCustomerBalanceAmount()) {
            $this->creditMemoHelper->setRefundCustomerbalanceReturnEnable(1);
            $this->creditMemoHelper->setRefundCustomerbalanceReturnAmount($order->getCustomerBalanceAmount());
        }

        $this->creditMemoHelper->create($order, []);
    }
}
