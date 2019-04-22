<?php

namespace RetailOps\Api\Model\Resource;

class Logger extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    protected function _construct()
    {
         $this->_init('retailops_order_status_history', 'id');
    }
}
