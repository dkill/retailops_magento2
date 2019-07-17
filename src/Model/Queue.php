<?php
namespace Gudtech\RetailOps\Model;

use \Gudtech\RetailOps\Model\QueueInterface;
use \Magento\Framework\DataObject\IdentityInterface;

/**
 * Queue class.
 *
 */
class Queue extends \Magento\Framework\Model\AbstractModel implements QueueInterface, IdentityInterface
{
    const CACHE_TAG = 'retailops_api_queue';
    protected $_idFieldName = self::ID;

    protected function _construct()
    {
        $this->_init(\Gudtech\RetailOps\Model\ResourceModel\Queue::class);
    }

    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }

    /**
     * @param string $message
     * @return $this
     */
    public function setMessage($message)
    {
        return parent::setData(self::MESSAGE, $message);
    }

    /**
     * @param bool $active
     * @return $this
     */
    public function setIsActive(bool $active)
    {
        return parent::setData(self::ACTIVE, $active);
    }

    /**
     * @param integer $type
     * @return mixed
     */
    public function setQueueType($type = QueueInterface::CANCEL_TYPE)
    {
        return parent::setData(self::QUEUE_TYPE, $type);
    }

    /**
     * @return string|null
     */
    public function getMessage()
    {
        return parent::getData(self::MESSAGE);
    }

    /**
     * @return bool
     */
    public function getIsActive()
    {
        return parent::getData(self::ACTIVE);
    }

    public function setOrderId($orderInc)
    {
        return parent::setData(self::ORDER_ID, $orderInc);
    }

    public function getOrderId()
    {
        return parent::getData(self::ORDER_ID);
    }

    /**
     * @return integer
     */
    public function getQueueType()
    {
        return parent::getData(self::QUEUE_TYPE);
    }
}
