<?php

namespace Gudtech\RetailOps\Model\Order;

use Gudtech\RetailOps\Model\Api\Order\Complete as CompleteApi;

/**
 * Complete order class.
 *
 */
class Complete
{
    /**
     * @var CompleteApi
     */
    protected $completeApi;

    /**
     * Complete constructor.
     *
     * @param CompleteApi $completeApi
     */
    public function __construct(CompleteApi $completeApi)
    {
        $this->completeApi = $completeApi;
    }

    public function updateOrder($postData)
    {
        if (!isset($postData['order']) || !isset($postData['order']['shipments'])) {
            throw new \LogicException(__("Don't have valid data"));
        }
        $this->completeApi->completeOrder($postData['order']);
    }
}
