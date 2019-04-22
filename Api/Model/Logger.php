<?php

namespace RetailOps\Api\Model;

class Logger extends \Magento\Framework\Model\AbstractModel
{
    protected function _construct()
    {
        $this->_init('\RetailOps\Api\Model\Resource\Logger');
    }
}
