<?php

namespace Gudtech\RetailOps\Model;

/**
 * Logger class.
 *
 */
class Logger extends \Magento\Framework\Model\AbstractModel
{
    protected function _construct()
    {
        $this->_init(\Gudtech\RetailOps\Model\ResourceModel\Logger::class);
    }
}
