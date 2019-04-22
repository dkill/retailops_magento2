<?php

namespace RetailOps\Api\Model\ResourceModel\Collection\Logger;

/**
 * Logger collection class
 */
class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected function _construct()
    {
        $this->_init(
            \RetailOps\Api\Model\Logger::class,
            \RetailOps\Api\Model\ResourceModel\Logger::class
        );
    }
}
