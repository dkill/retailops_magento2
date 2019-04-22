<?php

namespace RetailOps\Api\Service;

interface CalculateItemPriceInterface
{
    public function calculate(\Magento\Sales\Api\Data\OrderItemInterface $item);

    public function calculateItemTax(\Magento\Sales\Api\Data\OrderItemInterface $item);
}
