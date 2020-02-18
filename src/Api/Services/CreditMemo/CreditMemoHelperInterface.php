<?php

namespace Gudtech\RetailOps\Api\Services\CreditMemo;

use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderItemInterface;

/**
 * Credit memo helper interface.
 *
 */
interface CreditMemoHelperInterface
{
    /**
     * @param OrderItemInterface $orderItem
     * @param $value
     * @return float
     */
    public function getQuantity(OrderItemInterface $orderItem, $value);

    /**
     * @param $order
     * @param array $items
     * @return boolean;
     */
    public function create(OrderInterface $order, array $items);
}
