<?php

namespace Gudtech\RetailOps\Api;

use Magento\Sales\Api\Data\OrderInterface;

/**
 * Items manager interface
 *
 */
interface ItemsManagerInterface
{
    /**
     * @param OrderInterface $order
     * @param array $items
     * @return array
     */
    public function removeCancelItems(OrderInterface $order, array $items);

    /**
     * @param OrderInterface $order
     * @param array $items
     * @return array
     */
    public function removeInvoicedAndShippedItems(OrderInterface $order, array $items);

    /**
     * @return array
     */
    public function getCancelItems();

    /**
     * @return array
     */
    public function getNeedInvoiceItems();
}
