<?php

namespace RetailOps\Api\Service;

interface CalculateDiscountInterface
{
    /**
     * @param $item
     * @return float
     */
    public function calculate(\Magento\Sales\Api\Data\OrderInterface $item):float;
}
