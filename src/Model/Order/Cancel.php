<?php

namespace Gudtech\RetailOps\Model\Order;

use Magento\Framework\App\ObjectManager;

/**
 * Cancel order class.
 *
 */
class Cancel
{
    const QUEUE = 'retailops/retailops_advanced/cancel_queue';
    /**
     * @var \Gudtech\RetailOps\Model\Api\Order\Cancel
     */
    protected $cancelOrder;

    /**
     * @var \Gudtech\RetailOps\Model\Queue\Cancel
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
     * @param \\Gudtech\RetailOps\Model\Api\Order\Cancel $cancelOrder
     */
    public function __construct(
        \Gudtech\RetailOps\Model\Api\Order\Cancel $cancelOrder,
        \Gudtech\RetailOps\Model\Api\Queue\Cancel $cancelQueue
    ) {
        $this->cancelOrder = $cancelOrder;
        $this->cancelQueue = $cancelQueue;
    }
}
