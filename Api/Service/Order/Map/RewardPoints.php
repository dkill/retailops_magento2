<?php

namespace RetailOps\Api\Service\Order\Map;

class RewardPoints implements RewardPointsInterface
{
    public function getRewardsPointsPaymentTransaction(float $discount, \Magento\Sales\Api\Data\OrderInterface $order) : float
    {
        $rewardPoints = $order->getBaseRewardCurrencyAmount();
        if ((float)$rewardPoints > 0) {
            $discount += $rewardPoints;
        }

        return (float)$discount;
    }
}
