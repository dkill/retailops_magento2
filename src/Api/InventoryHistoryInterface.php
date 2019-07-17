<?php

namespace Gudtech\RetailOps\Api;

/**
 * Inventory history interface
 *
 */
interface InventoryHistoryInterface
{
    /**
     * Save history.
     *
     * @param InventoryHistoryInterface $history
     * @return Data\InventoryHistoryInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function save(\Gudtech\RetailOps\Api\Data\InventoryHistoryInterface $history);

    /**
     * Get history.
     *
     * @param int $historyId
     * @return \Gudtech\RetailOps\Api\InventoryHistoryInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getById($historyId);

    /**
     * Retrieve pages matching the specified criteria.
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Gudtech\RetailOps\Api\Data\InventoryHistorySearchInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(\Magento\Framework\Api\SearchCriteriaInterface $searchCriteria);

    /**
     * @param $historyId
     * @return $this
     */
    public function load($historyId);
}
