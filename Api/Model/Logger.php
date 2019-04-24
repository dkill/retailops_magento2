<?php

namespace RetailOps\Api\Model;

/**
 * Logger class.
 *
 */
class Logger extends \Magento\Framework\Model\AbstractModel
{
    protected function _construct()
    {
        $this->_init(\RetailOps\Api\Model\ResourceModel\Logger::class);
    }
}
