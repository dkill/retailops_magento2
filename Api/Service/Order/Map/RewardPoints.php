<?php

namespace RetailOps\Api\Service\Order\Map;

use \Magento\Sales\Api\Data\OrderInterface;

/**
 * Rewards point class.
 *
 */
class RewardPoints implements RewardPointsInterface
{
    public function getRewardsPointsPaymentTransaction(float $discount, OrderInterface $order) : float
    {
        $rewardPoints = $order->getBaseRewardCurrencyAmount();
        if ((float)$rewardPoints > 0) {
            $discount += $rewardPoints;
        }

        return (float)$discount;
    }
}
