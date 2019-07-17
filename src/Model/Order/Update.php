<?php

namespace Gudtech\RetailOps\Model\Order;

/**
 * Order update class.
 *
 */
class Update
{
    protected $updateOrder;

    public function __construct(\Gudtech\RetailOps\Model\Api\Order\Update $updateOrder)
    {
        $this->updateOrder = $updateOrder;
    }

    public function updateOrder($postData)
    {
        if ($postData['rmas'] === null && $postData['order'] === null) {
            throw new \LogicException(__('Don\'t have rmas or order for updates'));
        }
        $this->updateOrder->updateOrder($postData);
    }
}
