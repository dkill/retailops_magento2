<?php

namespace RetailOps\Api\Api\Services\CreditMemo;

interface CreditMemoHelperInterface
{
    /**
     * @param \Magento\Sales\Api\Data\OrderItemInterface $orderItem
     * @param $value
     * @return float
     */
    public function getQuantity(\Magento\Sales\Api\Data\OrderItemInterface $orderItem, $value);

    /**
     * @param $order
     * @param array $items
     * @return boolean;
     */
    public function create(\Magento\Sales\Api\Data\OrderInterface $order, array $items);
}
