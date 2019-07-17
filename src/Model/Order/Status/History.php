<?php

namespace Gudtech\RetailOps\Model\Order\Status;

/**
 * Order status history class.
 *
 */
class History extends \Magento\Framework\Model\AbstractModel
{
    protected function _construct()
    {
        $this->_init(\Gudtech\RetailOps\Model\ResourceModel\Order\Status\History::class);
    }
}
