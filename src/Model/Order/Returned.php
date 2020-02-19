<?php

namespace Gudtech\RetailOps\Model\Order;

use Gudtech\RetailOps\Model\Api\Order\Returned as ReturnedApi;

/**
 * Order return class.
 *
 */
class Returned
{
    /**
     * @var ReturnedApi
     */
    protected $returnedApi;

    /**
     * OrderReturn constructor.
     *
     * @param ReturnedApi $returnedApi
     */
    public function __construct(ReturnedApi $returnedApi)
    {
        $this->returnedApi = $returnedApi;
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
        $this->returnedApi->returnOrder($orderId, $postData['return']);
    }
}
