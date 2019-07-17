<?php

namespace Gudtech\RetailOps\Service;

/**
 * Calculate discount interface
 *
 */
interface CalculateDiscountInterface
{
    /**
     * @param $item
     * @return float
     */
    public function calculate(\Magento\Sales\Api\Data\OrderInterface $item):float;
}
