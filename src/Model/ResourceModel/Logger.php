<?php

namespace Gudtech\RetailOps\Model\ResourceModel;

/**
 * Logger resource model class.
 *
 */
class Logger extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    protected function _construct()
    {
         $this->_init('retailops_order_status_history', 'id');
    }
}
