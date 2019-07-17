<?php

namespace Gudtech\RetailOps\Ui\Component\Listing\DataProviders\Queue\Cancel;

use \Gudtech\RetailOps\Model\QueueInterface;

/**
 * Queue grid data provider class.
 *
 */
class Grid extends \Magento\Ui\DataProvider\AbstractDataProvider
{

    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        \Gudtech\RetailOps\Model\ResourceModel\Queue\CollectionFactory $collectionFactory,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->collection = $collectionFactory->create();
        $this->collection->addFieldToFilter(QueueInterface::ACTIVE, 1);
        $this->collection->addFieldToFilter(QueueInterface::QUEUE_TYPE, QueueInterface::CANCEL_TYPE);
    }
}
