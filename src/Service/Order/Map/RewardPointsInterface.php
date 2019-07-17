<?php

namespace Gudtech\RetailOps\Service\Order\Map;

/**
 * Interface RewardPointsInterface
 * @package Gudtech\RetailOps\Service\Order\Map
 */
interface RewardPointsInterface
{
    /**
     * @param array $payments
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @return array $payments
     */
    public function getRewardsPointsPaymentTransaction(float $discount, \Magento\Sales\Api\Data\OrderInterface $order);
}
