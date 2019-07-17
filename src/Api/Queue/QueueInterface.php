<?php

namespace Gudtech\RetailOps\Api\Queue;

/**
 * Queue interface
 *
 */
interface QueueInterface
{
    /**
     * @return mixed
     */
    public function setToQueue($message, \Magento\Sales\Api\Data\OrderInterface $order, $type);

    /**
     * @param $id
     * @return mixed
     */
    public function getFromQueue($id);
}
