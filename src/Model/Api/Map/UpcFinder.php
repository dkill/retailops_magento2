<?php

namespace Gudtech\RetailOps\Model\Api\Map;

use \Gudtech\RetailOps\Api\Order\Map\UpcFinderInterface;
use \Gudtech\RetailOps\Api\Data\RetailOpsRicsLinkByUpcRepositoryInterface;

/**
 * UPC finder class.
 *
 */
class UpcFinder implements UpcFinderInterface
{
    /**
     * @var RetailOpsRicsLinkByUpcRepositoryInterface
     */
    protected $repository;
    
    /**
     * @param \Magento\Sales\Api\Data\OrderItemInterface $orderItem
     * @param \Magento\Catalog\Api\Data\ProductInterface|null $product
     * @return string
     */
    public function getUpc(
        \Magento\Sales\Api\Data\OrderItemInterface $orderItem,
        \Magento\Catalog\Api\Data\ProductInterface $product = null
    ) {
        if ($product !== null) {
            $upcValue = $product->getUpc();
        } else {
            $upcValue = $this->getUpcBySku($orderItem);
        }
        $upc = $this->repository->getRoUpc($upcValue);
        if ($upc->getId()) {
            return (string)$upc->getUpc();
        }
        return $upcValue;
    }

    /**
     * This function actual only for sku, where sku = upc + 's'
     * @param \Magento\Sales\Api\Data\OrderItemInterface $orderItem
     * @return string
     */
    public function getUpcBySku(\Magento\Sales\Api\Data\OrderItemInterface $orderItem)
    {
        return ltrim($orderItem->getSku(), '\S\s');
    }

    public function __construct(RetailOpsRicsLinkByUpcRepositoryInterface $linkByUpcRepository)
    {
        $this->repository = $linkByUpcRepository;
    }

    public function setRoUpc($upc)
    {
        $this->repository->setRoUpc($upc);
    }
}