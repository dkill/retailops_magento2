<?php

namespace Gudtech\RetailOps\Model\Order;

use Gudtech\RetailOps\Model\Api\Order\Cancel as OrderCancel;
use Magento\Framework\App\ObjectManager;

/**
 * Cancel order class.
 *
 */
class Cancel
{
    /**
     * @var OrderCancel
     */
    protected $cancelOrder;

    /**
     * Cancel constructor.
     *
     * @param OrderCancel $cancelOrder
     */
    public function __construct(
        OrderCancel $cancelOrder
    ) {
        $this->cancelOrder = $cancelOrder;
    }

    /**
     * Cancel the order
     *
     * @param $postData
     * @return array
     */
    public function cancelOrder($postData)
    {
        if ($postData['order']) {
            return $this->cancelOrder->cancel($postData['order']);
        }
        return [];
    }
}
