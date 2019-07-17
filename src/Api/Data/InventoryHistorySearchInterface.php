<?php

namespace Gudtech\RetailOps\Api\Data;

/**
 * Inventory history search interface.
 *
 */
interface InventoryHistorySearchInterface
{
    /**
     * Get pages list.
     *
     * @return \Gudtech\RetailOps\Api\InventoryHistoryInterface[]
     */
    public function getItems();

    /**
     * Set pages list.
     *
     * @param \Gudtech\RetailOps\Api\InventoryHistoryInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
