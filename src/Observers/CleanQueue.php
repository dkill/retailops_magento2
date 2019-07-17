<?php

namespace Gudtech\RetailOps\Observers;

use Magento\Framework\App\ObjectManager;
use \Gudtech\RetailOps\Model\Order\Cancel;

/**
 * Clean queue observer class.
 *
 */
class CleanQueue implements \Magento\Framework\Event\ObserverInterface
{
    const ORDER_STATUS = [
        'canceled',
        'closed'
    ];

    /**
     * @var \Gudtech\RetailOps\Model\QueueRepository
     */
    protected $queueRepository;

    public function __construct(\Gudtech\RetailOps\Model\QueueRepository  $queueRepository)
    {
        $this->queueRepository = $queueRepository;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /**
         * @var \Magento\Sales\Model\Order $order
         */
        $order = $observer->getOrder();
        $scopeConfig = ObjectManager::getInstance()->get(\Magento\Framework\App\Config\ScopeConfigInterface::class);
        if ($scopeConfig->getValue(Cancel::QUEUE)) {
            if (in_array($order->getStatus(), self::ORDER_STATUS) &&
                !$order->isObjectNew() && $order->hasDataChanges()
            ) {
                $this->queueRepository->deleteByOrderInc($order->getIncrementId());
            }
        }
    }
}
