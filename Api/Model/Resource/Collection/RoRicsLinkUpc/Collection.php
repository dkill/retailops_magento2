<?php

namespace RetailOps\Api\Model\Resource\Collection\RoRicsLinkUpc;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected function _construct()
    {
        $this->_init(
            '\RetailOps\Api\Model\RoRicsLinkUpc',
            '\RetailOps\Api\Model\Resource\RoRicsLinkUpc'
        );
    }
}
