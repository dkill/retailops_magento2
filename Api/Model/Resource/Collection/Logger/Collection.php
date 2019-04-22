<?php

namespace RetailOps\Api\Model\Resource\Collection\Logger;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected function _construct()
    {
        $this->_init(
            '\RetailOps\Api\Model\Logger',
            '\RetailOps\Api\Model\Resource\Logger'
        );
    }
}
