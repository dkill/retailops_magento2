<?php

namespace RetailOps\Api\Model\ResourceModel;

/**
 * Inventory history resource class.
 *
 */
class InventoryHistory extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    protected function _construct()
    {
        $this->_init('retailops_inventory_history', 'id');
    }
}
