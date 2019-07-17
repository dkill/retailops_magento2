<?php

namespace Gudtech\RetailOps\Model\ResourceModel\Order\Status;

/**
 * Order status history resource model class.
 *
 */
class History extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    protected function _construct()
    {
        $this->_init('retailops/order_status_history', 'id');
    }
}
