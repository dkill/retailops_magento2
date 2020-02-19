<?php

namespace Gudtech\RetailOps\Model\Api;

use Exception;
use Gudtech\RetailOps\Model\Logger\Monolog;
use LogicException;
use Magento\Framework\Api\FilterFactory;
use Magento\Framework\Api\Search\FilterGroupFactory;
use Magento\Framework\Api\SearchCriteria;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;

/**
 * Acknowlegde class.
 *
 */
class Acknowledge
{
    use Traits\Filter;

    /**
     * Order status for acknowledged orders.
     *
     * @var integer
     */
    const ORDER_STATUS_ACKNOWLEDGED = 2;

    /**
     * In this array we save data, such as
     * ['magento_order_id'=>retailops_order_id]
     *
     * @var array
     */
    protected $linkOrderRetail;

    /**
     * Array where set information about error for retailOps response
     *
     * @var array
     */
    protected $events = [];

    /**
     * Acknowledge constructor.
     *
     * @param OrderRepositoryInterface $orderRepository
     * @param Monolog $logger
     * @param SearchCriteria $searchCriteria
     * @param FilterFactory $filter,
     * @param FilterGroupFactory $filterGroup
     */
    public function __construct(
        OrderRepositoryInterface $orderRepository,
        Monolog $logger,
        SearchCriteria $searchCriteria,
        FilterFactory $filter,
        FilterGroupFactory $filterGroup
    ) {
        $this->orderRepository = $orderRepository;
        $this->logger = $logger;
        $this->searchCriteria = $searchCriteria;
        $this->filter = $filter;
        $this->filterGroup = $filterGroup;
    }

    /**
     * @param array $orders
     * @return array
     * @throws LogicException
     */
    public function setOrderNumbers($orders)
    {
        try {
            $orderIds = $this->getOrderIds($orders);

            if (!count($orderIds)) {
                throw new LogicException(__("Don't have any numbers of orders"));
            }

            $filter = $this->createFilter('entity_id', 'in', array_keys($orderIds));
            $this->searchCriteria->setFilterGroups([$this->createFilterGroups([$filter])]);
            $result = $this->orderRepository->getList($this->searchCriteria);
            if ($result) {
                foreach ($result as $order) {
                    if (isset($orderIds[$order->getId()])) {

                        $order->setData('retailops_send_status', self::ORDER_STATUS_ACKNOWLEDGED);
                        $order->setStatus('retailops_processing');

                        if ($orderIds[$order->getId()] !== 0) {
                            $order->setData('retailops_order_id', $orderIds[$order->getId()]);
                            $order->addStatusToHistory(
                                $order->getStatus(),
                                "Acknowledged by RetailOps for processing. Order ID in RetailOps: ". $orderIds[$order->getId()]
                            );
                        } else {
                            $order->addStatusToHistory(
                                $order->getStatus(),
                                "Acknowledged by RetailOps for processing."
                            );
                        }
                        $order->save();
                    }

                }
            }
        } catch (Exception $exception) {
            $event = [];
            $event['event_type'] = 'error';
            $event['code'] = $exception->getCode();
            $event['message'] = $exception->getMessage();
            $event['diagnostic_data'] = $exception->getTrace();
            if (isset($order)) {
                $event['associations'] = [
                    'identifier_type' => 'order_refnum',
                    'identifier' => $order->getId()
                ];
            }
            $this->logger->addError('Error in acknowledge retailops', $event);
            $this->events = $event;
        } finally {
            return count($this->events) ? $this->events : (object) null;
        }
    }

    /**
     * @param string $eventType
     * @param string|null $code
     * @param string|null $message
     * @param string $identifierType
     * @param array|null $identifiers
     */
    protected function setEvent($eventType, $code, $message, $identifierType, $identifiers)
    {
        $event = [];
        $event['event_type'] = $eventType;
        $event['code'] = $code;
        $event['message'] = $message;
        if ($identifierType !== null && is_array($identifiers)) {
            $event['associations'] = [];
            foreach ($identifiers as $identifier) {
                $event['associations'][] = [
                    'identifier_type' => $identifierType,
                    'identifier' => $identifier
                ];
            }
        }
        $this->events = $event;
    }

    /**
     * @param  array $orders
     * @return array
     */
    protected function getOrderIds($orders)
    {
        if (!is_array($orders) || !count($orders)) {
            throw new LogicException(__("Don't have any order ids in request"));
        }

        $orderIds = [];

        foreach ($orders as $order) {
            if (isset($order['channel_order_refnum'])) {
                $orderIds[$order['channel_order_refnum']] = 0;
                if (isset($order['retailops_order_id'])) {
                    $orderIds[$order['channel_order_refnum']] = $order['retailops_order_id'];
                }
            }
        }

        return $this->setOrderIdByOrderIncrementId($orderIds);
    }
}
