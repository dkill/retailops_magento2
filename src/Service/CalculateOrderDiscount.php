<?php

namespace Gudtech\RetailOps\Service;

/**
 * Calculate order discount class.
 *
 */
class CalculateOrderDiscount implements CalculateDiscountInterface
{
    /**
     * @var Order\Map\RewardPointsInterface
     */
    private $rewardPoints;

    /**
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @return float
     */
    public function calculate(\Magento\Sales\Api\Data\OrderInterface $order):float
    {
        $discount = 0;
        $discount += abs($order->getBaseDiscountTaxCompensationAmount());
        $discount += abs($order->getBaseShippingDiscountAmount());
        $discount += abs($order->getBaseShippingDiscountTaxCompensationAmnt());
        $discount += abs($order->getBaseDiscountAmount());

        return $this->addRewardPoints($discount, $order);
    }

    /**
     * @param float $discount
     * @return float
     */
    public function addRewardPoints(float $discount, \Magento\Sales\Api\Data\OrderInterface $order) :float
    {
        return $this->_rewardPoints->getRewardsPointsPaymentTransaction($discount, $order);
    }

    /**
     * CalculateOrderDiscount constructor.
     * @param Order\Map\RewardPointsInterface $rewardPoints
     */
    public function __construct(\Gudtech\RetailOps\Service\Order\Map\RewardPointsInterface $rewardPoints)
    {
        $this->_rewardPoints = $rewardPoints;
    }
}
