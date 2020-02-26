<?php

namespace Gudtech\RetailOps\Model\Api\Order;

use Exception;
use Gudtech\RetailOps\Api\Services\CreditMemo\CreditMemoHelperInterface;
use Gudtech\RetailOps\Model\Api\Traits\Filter;
use Gudtech\RetailOps\Model\Logger\Monolog;
use Gudtech\RetailOps\Model\Order\Status\History;
use LogicException;
use Magento\Framework\Api\FilterFactory;
use Magento\Framework\Api\Search\FilterGroupFactory;
use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order\Email\Sender\OrderCommentSender;

/**
 * Cancel order class.
 *
 */
class Cancel
{
    use Filter;

    const EMAIL_COMMENT_CANCELLED = 'Custom message to the customer. Needs to be changed.';

    /**
     * @var CreditMemoHelperInterface
     */
    protected $creditMemoHelper;
    protected $response;
    protected $status = 'success';
    protected $events = [];

    /**
     * @var History
     */
    protected $historyRetail;

    /**
     * @var OrderCommentSender
     */
    private $orderCommentSender;

    /**
     * Cancel constructor.
     *
     * @param OrderRepositoryInterface $orderRepository
     * @param Monolog $logger
     * @param SearchCriteria $searchCriteria
     * @param FilterFactory $filter
     * @param FilterGroupFactory $filterGroup
     * @param History $historyRetail
     * @param CreditMemoHelperInterface $creditMemoHelper
     * @param OrderCommentSender $orderCommentSender
     */
    public function __construct(
        OrderRepositoryInterface $orderRepository,
        Monolog $logger,
        SearchCriteria $searchCriteria,
        FilterFactory $filter,
        FilterGroupFactory $filterGroup,
        History $historyRetail,
        CreditMemoHelperInterface $creditMemoHelper,
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
        } catch (Exception $e) {
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
     * Returns the order ID based on the order information.
     *
     * @param $orderId
     * @return string|null
     */
    protected function getOrderId($orderInfo)
    {
        if (isset($orderInfo['channel_order_refnum'])) {
            return $this->getOrderIdByOrderIncrementId($orderInfo['channel_order_refnum']);
        } else {
            $this->logger->addError('Invalid map', (array)$orderInfo);
            throw new LogicException(__('invalid map'));

        }
    }

    /**
     * Cancels an order
     *
     * @param   OrderInterface $order
     * @returns bool
     * @throws  LocalizedException
     */
    private function cancelOrder(OrderInterface $order)
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
     * @param OrderInterface $order
     */
    private function allRefund(OrderInterface $order)
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
