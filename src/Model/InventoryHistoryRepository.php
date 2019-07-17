<?php

namespace Gudtech\RetailOps\Model;

use Magento\Framework\Exception\LocalizedException;
use Gudtech\RetailOps\Api\InventoryHistoryInterface;
use Gudtech\RetailOps\Api\Data\InventoryHistoryInterface as InventoryHistoryDataInterface;

/**
 * Inventory history repository.
 *
 */
class InventoryHistoryRepository implements InventoryHistoryInterface
{
    use \Gudtech\RetailOps\Model\Api\Traits\SearchResult;

    /**
     * @var Resource\InventoryHistory
     */
    protected $resource;
    /**
     * @var Resource\Collection\InventoryHistory\CollectionFactory
     */
    protected $collectionFactory;
    /**
     * @var InventoryHistoryFactory
     */
    protected $inventoryHistoryFactory;

    /**
     * @var \Gudtech\RetailOps\Api\Data\InventoryHistorySearchInterfaceFactory
     */
    protected $searchResultFactory;
    /**
     * @param int $historyId
     */
    public function getById($historyId)
    {
        $inventoryHistory = $this->inventoryHistoryFactory->create();
        $inventoryHistory = $this->resource->load($inventoryHistory, $historyId);
        if (!$inventoryHistory->getId()) {
            throw new LocalizedException(__('no this id in database'));
        }
        return $inventoryHistory;
    }

    /**
     * @param InventoryHistoryDataInterface $history
     * @return InventoryHistoryDataInterface
     */
    public function save(InventoryHistoryDataInterface $history)
    {
        $history = $this->resource->save($history);
        return $history;
    }

    /**
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return InventoryHistoryDataInterface
     */
    public function getList(\Magento\Framework\Api\SearchCriteriaInterface $searchCriteria)
    {
        $collection = $this->collectionFactory->create();
        $searchData = $this->searchResultFactory->create();
        $this->prepareSearchData($searchCriteria, $searchData, $collection);
        return $searchData;
    }

    /**
     * @param $historyId
     * @return $this
     */
    public function load($historyId)
    {
        return $this->getById($historyId);
    }

    public function __construct(
        \Gudtech\RetailOps\Model\ResourceModel\InventoryHistory $resource,
        \Gudtech\RetailOps\Model\ResourceModel\Collection\InventoryHistory\CollectionFactory $collectionFactory,
        \Gudtech\RetailOps\Model\InventoryHistoryFactory $inventoryHistoryFactory,
        \Gudtech\RetailOps\Api\Data\InventoryHistorySearchInterfaceFactory $searchResult
    ) {
        $this->resource = $resource;
        $this->collectionFactory = $collectionFactory;
        $this->inventoryHistoryFactory = $inventoryHistoryFactory;
        $this->searchResultFactory = $searchResult;
    }
}
