<?php

namespace Gudtech\RetailOps\Model\Order;

/**
 * Complete order class.
 *
 */
class Complete
{
    protected $completeOrder;

    public function __construct(\Gudtech\RetailOps\Model\Api\Order\Complete $completeOrder)
    {
        $this->completeOrder = $completeOrder;
    }

    public function updateOrder($postData)
    {
        if (!isset($postData['order']) || !isset($postData['order']['shipments'])) {
            throw new \LogicException(__("Don't have valid data"));
        }
        $this->completeOrder->completeOrder($postData['order']);
    }
}
