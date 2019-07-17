<?php

namespace Gudtech\RetailOps\Model\ResourceModel\Collection\RoRicsLinkUpc;

/**
 * Ro Rics link UPC resource model collection class.
 *
 */
class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected function _construct()
    {
        $this->_init(
            \Gudtech\RetailOps\Model\RoRicsLinkUpc::class,
            \Gudtech\RetailOps\Model\ResourceModel\RoRicsLinkUpc::class
        );
    }
}
