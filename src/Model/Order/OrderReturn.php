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
     * Process the RMA.
     *
     * @param $postData
     */
    public function returnOrder($postData)
    {
        if (!isset($postData['order']) || !isset($postData['return'])) {
            throw new \LogicException(__("Don't have valid data"));
        }

        $orderId = $postData['order']['channel_order_refnum'];
        $this->orderReturn->returnOrder($orderId, $postData['return']);
    }
}
