<?php

namespace RetailOps\Api\Api\Data;

/**
 * Inventory history search interface.
 *
 */
interface InventoryHistorySearchInterface
{
    /**
     * Get pages list.
     *
     * @return \RetailOps\Api\Api\InventoryHistoryInterface[]
     */
    public function getItems();

    /**
     * Set pages list.
     *
     * @param \RetailOps\Api\Api\InventoryHistoryInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
