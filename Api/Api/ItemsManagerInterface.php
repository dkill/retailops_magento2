<?php

namespace RetailOps\Api\Api;

/**
 * Items manager interface
 *
 */
interface ItemsManagerInterface
{
    /**
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @param array $items
     * @return array
     */
    public function removeCancelItems(\Magento\Sales\Api\Data\OrderInterface $order, array $items);

    /**
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @param array $items
     * @return array
     */
    public function removeInvoicedAndShippedItems(\Magento\Sales\Api\Data\OrderInterface $order, array $items);

    /**
     * @return array
     */
    public function getCancelItems();
    
    /**
     * @return array
     */
    public function getNeedInvoiceItems();
}
