<?php

namespace Gudtech\RetailOps\Model\Product;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Collection\AbstractCollection;

/**
 * Product collection class.
 *
 */
class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init(
            Product::class,
            \Magento\Catalog\Model\ResourceModel\Product::class
        );
    }
}
