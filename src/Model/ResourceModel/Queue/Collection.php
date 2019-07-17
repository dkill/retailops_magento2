<?php

namespace Gudtech\RetailOps\Model\ResourceModel\Queue;

/**
 * Queue resource model collection class.
 *
 */
class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected $_idFieldName = 'gudtech_retailops_queue_id';

    protected function _construct()
    {
        $this->_init(
            \Gudtech\RetailOps\Model\Queue::class,
            \Gudtech\RetailOps\Model\ResourceModel\Queue::class
        );
    }
}
