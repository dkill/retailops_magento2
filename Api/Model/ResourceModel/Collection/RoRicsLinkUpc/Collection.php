<?php

namespace RetailOps\Api\Model\ResourceModel\Collection\RoRicsLinkUpc;

/**
 * Ro Rics link UPC resource model collection class.
 *
 */
class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected function _construct()
    {
        $this->_init(
            \RetailOps\Api\Model\RoRicsLinkUpc::class,
            \RetailOps\Api\Model\ResourceModel\RoRicsLinkUpc::class
        );
    }
}
