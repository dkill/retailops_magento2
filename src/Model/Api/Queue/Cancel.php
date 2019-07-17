<?php

namespace Gudtech\RetailOps\Model\Api\Queue;

use Gudtech\RetailOps\Api\Queue\QueueInterface;
use Gudtech\RetailOps\Model\QueueInterface as Queue;

/**
 * Queue cancel class.
 *
 */
class Cancel implements QueueInterface
{
    use \Gudtech\RetailOps\Model\Api\Traits\FullFilter;

    protected $response;
    protected $status = 'success';
    protected $events = [];

    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var \Gudtech\RetailOps\Model\QueueFactory
     */
    protected $queueFactory;

    /**
     * @var \Gudtech\RetailOps\Model\QueueRepository
     */
    protected $queueRepository;
    /**
     * @return Queue
     */
    public function setToQueue($message, \Magento\Sales\Api\Data\OrderInterface $order, $type = Queue::CANCEL_TYPE)
    {
        /**
         * @var \Gudtech\RetailOps\Model\Queue $queue
         */
        $queue = $this->queueFactory->create();
        $queue->setMessage($message);
        $queue->setQueueType($type);
        $queue->setOrderId($order->getIncrementId());
         return $this->queueRepository->save($queue);
    }

    /**
     * @param $id
     * @param $type
     * @return mixed
     */
    public function getFromQueue($id)
    {
        return $this->queueRepository->getById($id);
    }

    public function cancel($data)
    {
        $orderId = $this->getOrderIdByIncrement($data['channel_order_refnum']);
        /**
         * @var \Magento\Sales\Api\Data\OrderInterface $order
         */
        $order = $this->orderRepository->get($orderId);

        if ($order->getId()) {
            $message = $this->getMessage($order);
            $this->setToQueue($message, $order);
        }
        $response['status'] = $this->status;
        $response['events'] = $this->events;
        return $this->response = $response;
    }

    public function getMessage(\Magento\Sales\Api\Data\OrderInterface $order)
    {
        $incrementId = $order->getIncrementId();
        return sprintf(__('Cancel order number: %s'), $incrementId);
    }

    public function __construct(
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Gudtech\RetailOps\Model\QueueFactory $queue,
        \Gudtech\RetailOps\Model\QueueRepository $queueRepository
    ) {
        $this->orderRepository = $orderRepository;
        $this->queueFactory = $queue;
        $this->queueRepository = $queueRepository;
    }
}
