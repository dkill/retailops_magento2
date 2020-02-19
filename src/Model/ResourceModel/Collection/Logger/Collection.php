<?php

namespace Gudtech\RetailOps\Model\ResourceModel\Collection\Logger;

/**
 * Logger collection class
 */
class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected function _construct()
    {
        $this->_init(
            \Gudtech\RetailOps\Model\Logger::class,
            \Gudtech\RetailOps\Model\ResourceModel\Logger::class
        );
    }
}
