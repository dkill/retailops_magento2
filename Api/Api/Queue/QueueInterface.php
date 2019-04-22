<?php

namespace RetailOps\Api\Api\Queue;

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
