<?php

namespace Gudtech\RetailOps\Service\Order\Map;

use Gudtech\RetailOps\Api\Order\Map\CalculateAmountInterface;

/**
 * Calculate amount order map class.
 *
 */
class CalculateAmount implements CalculateAmountInterface
{
    /**
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @return float
     */
    public function calculateShipping(\Magento\Sales\Api\Data\OrderInterface $order)
    {
        $shippingAmount = (float)$order->getBaseShippingAmount()
                            -(float)$order->getBaseShippingCanceled()
                            -(float)$order->getBaseShippingRefunded();
        if ($shippingAmount < 0) {
            throw  new \LogicException(
                'Shipping amount is:'.$shippingAmount.', but amt cannot be negative, order:'.$order->getId()
            );
        }
        return $shippingAmount;
    }

    /**
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @return float
     */
    public function calculateGrandTotal(\Magento\Sales\Api\Data\OrderInterface $order)
    {
        $total = (float)$order->getBaseGrandTotal()
                        -(float)$order->getBaseTotalRefunded()
                        -(float)$order->getBaseTotalCanceled();
        if ($total < 0) {
            throw new \LogicException('Total amout cannot be negative, order:'.$order->getId());
        }
        return $total;
    }
}
