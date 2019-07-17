<?php

namespace Gudtech\RetailOps\Api\Order\Map;

/**
 * UPC finder interface
 *
 */
interface UpcFinderInterface
{
    /**
     * @param \Magento\Sales\Api\Data\OrderItemInterface $orderItem
     * @param \Magento\Catalog\Api\Data\ProductInterface|null $product
     * @return string|null
     */
    public function getUpc(
        \Magento\Sales\Api\Data\OrderItemInterface $orderItem,
        \Magento\Catalog\Api\Data\ProductInterface $product = null
    );

    /**
     * @param \Gudtech\RetailOps\Api\Data\RetailOpsRicsLinkByUpcInterface $upc
     * @return $this
     */
    public function setRoUpc($upc);
}
