<?php

namespace RetailOps\Api\Api\Order\Map;

interface CalculateAmountInterface
{
    /**
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @return float
     */
    public function calculateShipping(\Magento\Sales\Api\Data\OrderInterface $order);

    /**
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @return float
     */
    public function calculateGrandTotal(\Magento\Sales\Api\Data\OrderInterface $order);
}
