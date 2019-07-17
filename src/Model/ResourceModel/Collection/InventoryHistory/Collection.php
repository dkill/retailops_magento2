<?php

namespace Gudtech\RetailOps\Model\ResourceModel\Collection\InventoryHistory;

/**
 * Inventory history resource model collection class.
 *
 */
class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected function _construct()
    {
        $this->_init(
            \Gudtech\RetailOps\Model\InventoryHistory::class,
            \Gudtech\RetailOps\Model\ResourceModel\InventoryHistory::class
        );
    }
}
