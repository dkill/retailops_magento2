<?php

namespace Gudtech\RetailOps\Model\Inventory;

use Gudtech\RetailOps\Model\InventoryHistoryFactory;
use Gudtech\RetailOps\Model\InventoryHistoryRepository;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\Data\SourceItemInterfaceFactory;
use Magento\InventoryApi\Api\SourceItemRepositoryInterface;
use Magento\InventoryApi\Api\SourceItemsDeleteInterface;
use Magento\InventoryApi\Api\SourceItemsSaveInterface;
use Magento\Sales\Model\Order as MagentoOrder;
use Psr\Log\LoggerInterface;
use Magento\Framework\Indexer\CacheContext;
use Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory as OrderItemCollectionFactory;

/**
 * Inventory push class.
 */
class Push
{
    const RESERVED_QUANTITY_SOURCE_CODE = ['SB'];

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var SearchCriteriaBuilderFactory
     */
    private $searchCriteriaBuilderFactory;

    /**
     * @var SourceItemRepositoryInterface
     */
    private $sourceItemRepository;

    /**
     * @var SourceItemInterfaceFactory
     */
    private $sourceItemFactory;

    /**
     * @var SourceItemsSaveInterface
     */
    private $sourceItemsSave;

    /**
     * @var SourceItemsDeleteInterface
     */
    private $sourceItemsDelete;

    /**
     * @var InventoryHistoryInterface
     */
    private $inventoryHistoryRepository;

    /**
     * @var InventoryHistoryFactory
     */
    private $inventoryHistoryFactory;

    /**
     * @var OrderItemCollectionFactory
     */
    private $orderItemCollectionFactory;

    public function __construct(
        ProductRepositoryInterface $productRepository,
        SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory,
        SourceItemRepositoryInterface $sourceItemRepository,
        SourceItemInterfaceFactory $sourceItemFactory,
        SourceItemsSaveInterface $sourceItemsSave,
        SourceItemsDeleteInterface $sourceItemsDelete,
        InventoryHistoryRepository $inventoryHistoryRepository,
        InventoryHistoryFactory $inventoryHistoryFactory,
        OrderItemCollectionFactory $orderItemCollectionFactory
    ) {
        $this->productRepository = $productRepository;
        $this->searchCriteriaBuilderFactory = $searchCriteriaBuilderFactory;
        $this->sourceItemRepository = $sourceItemRepository;
        $this->sourceItemFactory = $sourceItemFactory;
        $this->sourceItemsSave = $sourceItemsSave;
        $this->sourceItemsDelete = $sourceItemsDelete;
        $this->inventoryHistoryRepository = $inventoryHistoryRepository;
        $this->inventoryHistoryFactory = $inventoryHistoryFactory;
        $this->orderItemCollectionFactory = $orderItemCollectionFactory;
    }

    /**
     * Processes the inventory data.
     *
     * @param array $inventory
     */
    public function processData($inventory)
    {
        // Make sure SKU exists, throws exception when product can't be found.
        $product = $this->productRepository->get($inventory['sku']);

        $sourceItemsForDelete = $this->getCurrentSourceItemsMap($inventory['sku']);
        $sourceItemsForSave = [];

        foreach ($inventory['quantity_detail'] as $sourceItemData) {
            $sourceCode = $sourceItemData['facility_name'];

            if (isset($sourceItemsForDelete[$sourceCode])) {
                $sourceItem = $sourceItemsForDelete[$sourceCode];
            } else {
                /** @var SourceItemInterface $sourceItem */
                $sourceItem = $this->sourceItemFactory->create();
                $sourceItem->setSourceCode($sourceCode);
            }

            $currentQuantity = $sourceItem->getQuantity();

            if (in_array($sourceCode, self::RESERVED_QUANTITY_SOURCE_CODE)) {
                $reservedQuantity = $this->getReservedQuantity($inventory['sku']);
                $newQuantity = $sourceItemData['available_quantity'] - $reservedQuantity;
            } else {
                $newQuantity = $sourceItemData['available_quantity'];
            }

            $sourceItem->setSku($inventory['sku']);
            $sourceItem->setQuantity($sourceItemData['available_quantity']);
            if ($sourceItemData['available_quantity'] > 0) {
                $sourceItem->setStatus(SourceItemInterface::STATUS_IN_STOCK);
            } else {
                $sourceItem->setStatus(SourceItemInterface::STATUS_OUT_OF_STOCK);
            }

            $sourceItemsForSave[] = $sourceItem;
            unset($sourceItemsForDelete[$sourceCode]);

            $inventoryHistory = $this->inventoryHistoryFactory->create();
            $inventoryHistory->setProductId($product->getId());
            $inventoryHistory->setSourceCode($sourceCode);
            $inventoryHistory->setRetailopsQuantity($sourceItemData['available_quantity']);
            $inventoryHistory->setMagentoQuantity($currentQuantity);
            $inventoryHistory->setReservedQuantity($reservedQuantity);
            $inventoryHistory->setNewQuantity($newQuantity);
            $this->inventoryHistoryRepository->save($inventoryHistory);
        }

        if ($sourceItemsForSave) {
            $this->sourceItemsSave->execute($sourceItemsForSave);
        }
        if ($sourceItemsForDelete) {
            $this->sourceItemsDelete->execute($sourceItemsForDelete);
        }

        $this->productRepository->save($product);
    }

    /**
     * Get Source Items Hash Table by SKU
     *
     * @param string $sku
     * @return SourceItemInterface[]
     */
    private function getCurrentSourceItemsMap(string $sku): array
    {
        /** @var SearchCriteriaBuilder $searchCriteriaBuilder */
        $searchCriteriaBuilder = $this->searchCriteriaBuilderFactory->create();
        $searchCriteria = $searchCriteriaBuilder->addFilter(ProductInterface::SKU, $sku)->create();
        $sourceItems = $this->sourceItemRepository->getList($searchCriteria)->getItems();

        $sourceItemMap = [];
        if ($sourceItems) {
            foreach ($sourceItems as $sourceItem) {
                $sourceItemMap[$sourceItem->getSourceCode()] = $sourceItem;
            }
        }
        return $sourceItemMap;
    }

    /**
     * Retrieves the reserved product quantity for orders which are not synced yet to RetailOps.
     *
     * @param $sku
     * @return int
     */
    private function getReservedQuantity($sku)
    {
        $reservedQuantity = 0;

        /**
         * @var \Magento\Sales\Model\ResourceModel\Order\Item\Collection $collection
         */
        $collection = $this->orderItemCollectionFactory->create();
        $connection = $collection->getConnection();

        $collection->getSelect()->reset(\Magento\Framework\DB\Select::COLUMNS);
        $collection->getSelect()->columns([new \Zend_Db_Expr('SUM(main_table.qty_ordered) - SUM(main_table.qty_canceled) - SUM(main_table.qty_refunded) AS reserved_quantity')]
        );
        $collection->getSelect()->joinLeft(
            ['so' => $connection->getTableName('sales_order')],
            'so.entity_id = main_table.order_id',
            []
        );
        $collection->getSelect()->where(
            'so.retailops_send_status=?',
            \Gudtech\RetailOps\Model\Api\Map\Order::ORDER_STATUS_PENDING
        );
        $collection->getSelect()->where('main_table.sku = ?', $sku);
        $collection->getSelect()->where(
            'so.state NOT IN (?)',
            [MagentoOrder::STATE_CANCELED, MagentoOrder::STATE_CLOSED, MagentoOrder::STATE_COMPLETE]
        );
        $collection->getSelect()->where(
            'main_table.product_type = ?',
            \Magento\Catalog\Model\Product\Type::TYPE_SIMPLE
        );
        $collection->getSelect()->group('main_table.sku');
        $collection->load();

        foreach ($collection as $item) {
            $reservedQuantity = $reservedQuantity + $item->getReservedQuantity;
        }

        return $reservedQuantity;
    }
}
