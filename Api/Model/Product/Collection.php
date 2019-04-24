<?php

namespace RetailOps\Api\Model\Product;

/**
 * Product collection class.
 *
 */
class Collection extends \Magento\Catalog\Model\ResourceModel\Collection\AbstractCollection
{
    protected function _construct()
    {
        $this->_init(
            \Magento\Catalog\Model\Product::class,
            \Magento\Catalog\Model\ResourceModel\Product::class
        );
    }
}
