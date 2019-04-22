<?php
namespace RetailOps\Api\Ui\Component\Listing\DataProviders\Retailops\Api;

/**
 * RetailOps Api log class.
 *
 */
class Log extends \Magento\Ui\DataProvider\AbstractDataProvider
{

    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        \RetailOps\Api\Model\ResourceModel\Collection\InventoryHistory\CollectionFactory $collectionFactory,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->collection = $collectionFactory->create();
    }
}
