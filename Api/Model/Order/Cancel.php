<?php

namespace RetailOps\Api\Model\Order;

use Magento\Framework\App\ObjectManager;

/**
 * Cancel order class.
 *
 */
class Cancel
{
    const QUEUE = 'retailops/RetailOps_advanced/cancel_queue';
    /**
     * @var \RetailOps\Api\Model\Api\Order\Cancel
     */
    protected $cancelOrder;

    /**
     * @var \RetailOps\Api\Model\Queue\Cancel
     */
    protected $cancelQueue;

    public function cancelOrder($postData)
    {
        if ($postData['order']) {
            $scopeConfig = ObjectManager::getInstance()->get(\Magento\Framework\App\Config\ScopeConfigInterface::class);
            if (!$scopeConfig->getValue(self::QUEUE)) {
                $response = $this->cancelOrder->cancel($postData['order']);
            } else {
                $response = $this->cancelQueue->cancel($postData['order']);
            }
            return $response;
        }
        return [];
    }

    /**
     * Cancel constructor.
     * @param \\RetailOps\Api\Model\Api\Order\Cancel $cancelOrder
     */
    public function __construct(
        \RetailOps\Api\Model\Api\Order\Cancel $cancelOrder,
        \RetailOps\Api\Model\Api\Queue\Cancel $cancelQueue
    ) {
        $this->cancelOrder = $cancelOrder;
        $this->cancelQueue = $cancelQueue;
    }
}
