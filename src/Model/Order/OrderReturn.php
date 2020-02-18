<?php

namespace Gudtech\RetailOps\Model\Order;

use Gudtech\RetailOps\Model\Api\Order\OrderReturn as OrderReturnApi;

/**
 * Order return class.
 *
 */
class OrderReturn
{
    /**
     * @var OrderReturnApi
     */
    protected $orderReturn;

    /**
     * OrderReturn constructor.
     *
     * @param OrderReturnApi $orderReturn
     */
    public function __construct(OrderReturnApi $orderReturn)
    {
        $this->orderReturn = $orderReturn;
    }

    /**
     * Process returning the order.
     *
     * @param $data
     */
    public function returnOrder($data)
    {
        $this->orderReturn->returnOrder($data);
    }
}
