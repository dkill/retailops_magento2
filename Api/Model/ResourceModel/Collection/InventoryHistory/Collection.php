<?php

namespace RetailOps\Api\Model\ResourceModel\Collection\InventoryHistory;

/**
 * Inventory history resource model collection class.
 *
 */
class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected function _construct()
    {
        $this->_init(
            \RetailOps\Api\Model\InventoryHistory::class,
            \RetailOps\Api\Model\ResourceModel\InventoryHistory::class
        );
    }
}
