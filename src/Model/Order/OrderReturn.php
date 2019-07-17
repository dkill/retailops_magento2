<?php

namespace Gudtech\RetailOps\Model\Order;

/**
 * Order return class.
 *
 */
class OrderReturn
{
    /**
     * @var \Gudtech\RetailOps\Model\Api\Order\OrderReturn
     */
    protected $orderReturn;

    public function __construct(\Gudtech\RetailOps\Model\Api\Order\OrderReturn $orderReturn)
    {
        $this->orderReturn = $orderReturn;
    }

    public function returnOrder($data)
    {
        $this->orderReturn->returnData($data);
    }
}
