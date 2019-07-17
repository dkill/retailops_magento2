<?php
namespace Gudtech\RetailOps\Ui\Component\Listing\DataProviders\Gudtech\RetailOps;

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
        \Gudtech\RetailOps\Model\ResourceModel\Collection\InventoryHistory\CollectionFactory $collectionFactory,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->collection = $collectionFactory->create();
    }
}
