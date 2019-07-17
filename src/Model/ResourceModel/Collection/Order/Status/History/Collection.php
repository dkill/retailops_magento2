<?php

namespace Gudtech\RetailOps\Model\ResourceModel\Collection\Order\Status\History;

/**
 * Order status history collection class.
 *
 */
class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected function _construct()
    {
        $this->_init(
            \Gudtech\RetailOps\Model\Order\Status\History::class,
            \Gudtech\RetailOps\Model\ResourceModel\Order\Status\History::class
        );
    }
}
