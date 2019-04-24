<?php

namespace RetailOps\Api\Model\Order\Status;

/**
 * Order status history class.
 *
 */
class History extends \Magento\Framework\Model\AbstractModel
{
    protected function _construct()
    {
        $this->_init(\RetailOps\Api\Model\ResourceModel\Order\Status\History::class);
    }
}
