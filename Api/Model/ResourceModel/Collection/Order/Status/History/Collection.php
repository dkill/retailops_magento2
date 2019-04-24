<?php

namespace RetailOps\Api\Model\ResourceModel\Collection\Order\Status\History;

/**
 * Order status history collection class.
 *
 */
class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected function _construct()
    {
        $this->_init(
            \RetailOps\Api\Model\Order\Status\History::class,
            \RetailOps\Api\Model\ResourceModel\Order\Status\History::class
        );
    }
}
