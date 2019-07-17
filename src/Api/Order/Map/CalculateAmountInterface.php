<?php

namespace Gudtech\RetailOps\Api\Order\Map;

use Magento\Sales\Api\Data\OrderInterface;

/**
 * Calculate amount interface
 *
 */
interface CalculateAmountInterface
{
    /**
     * @param OrderInterface $order
     * @return float
     */
    public function calculateShipping(OrderInterface $order);

    /**
     * @param OrderInterface $order
     * @return float
     */
    public function calculateGrandTotal(OrderInterface $order);
}
