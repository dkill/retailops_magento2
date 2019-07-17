<?php

namespace Gudtech\RetailOps\Api\Order\Map;

use Gudtech\RetailOps\Api\Data\RetailOpsRicsLinkByUpcInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Sales\Api\Data\OrderItemInterface;

/**
 * UPC finder interface
 *
 */
interface UpcFinderInterface
{
    /**
     * @param OrderItemInterface $orderItem
     * @param ProductInterface|null $product
     * @return string|null
     */
    public function getUpc(
        OrderItemInterface $orderItem,
        ProductInterface $product = null
    );

    /**
     * @param RetailOpsRicsLinkByUpcInterface $upc
     * @return $this
     */
    public function setRoUpc($upc);
}
