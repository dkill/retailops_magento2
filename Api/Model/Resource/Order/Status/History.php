<?php

namespace RetailOps\Api\Model\Resource\Order\Status;

class History extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    protected function _construct()
    {
        $this->_init('retailops/order_status_history', 'id');
    }
}
