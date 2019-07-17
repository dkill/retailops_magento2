<?php

namespace Gudtech\RetailOps\Service;

/**
 * Calculate item price interface.
 *
 */
interface CalculateItemPriceInterface
{
    public function calculate(\Magento\Sales\Api\Data\OrderItemInterface $item);

    public function calculateItemTax(\Magento\Sales\Api\Data\OrderItemInterface $item);
}
