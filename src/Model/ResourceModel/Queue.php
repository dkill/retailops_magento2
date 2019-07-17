<?php
namespace Gudtech\RetailOps\Model\ResourceModel;

/**
 * Queue resource model class.
 *
 */
class Queue extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    protected function _construct()
    {
        $this->_init('gudtech_retailops_queue', 'gudtech_retailops_queue_id');
    }
}
