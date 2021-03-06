<?php

namespace Gudtech\RetailOps\Service;

/**
 * Items manager class.
 *
 */
class ItemsManager implements \Gudtech\RetailOps\Api\ItemsManagerInterface
{
    /**
     * @var array
     */
    protected $cancelItems = [];

    /**
     * @var array
     */
    protected $needShipmentItems = [];

    /**
     * @var array
     */
    protected $needInvoiceItems = [];

    /**
     * @return array
     */
    public function getCancelItems()
    {
        return $this->cancelItems;
    }

    /**
     * @return array
     */
    public function getNeedInvoiceItems()
    {
        return $this->needInvoiceItems;
    }

    /**
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @param array $allItems
     * @return array
     */
    public function removeCancelItems(\Magento\Sales\Api\Data\OrderInterface $order, array $allItems)
    {
        $creditMemoItems = [];
        foreach ($order->getItems() as $orderItem) {
            if (!$orderItem->getParentItemId() && array_key_exists($orderItem->getSku(), $allItems)) {
                $quantity = (float)$allItems[$orderItem->getSku()];
                $delta = $quantity - (float)$orderItem->getQtyToCanceled();
                if ($delta <= 0) {
                    $this->cancelItems[$orderItem->getId()] = $delta;
                } else {
                    $this->cancelItems[$orderItem->getId()] = $orderItem->getQtyToCanceled();
                    $creditMemoItems[$orderItem->getId()] = $delta;
                }
            }
        }

        return $creditMemoItems;
    }

    /**
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @param array $items
     */
    public function removeInvoicedAndShippedItems(\Magento\Sales\Api\Data\OrderInterface $order, array $items)
    {
        foreach ($order->getItems() as $orderItem) {
            if (array_key_exists($orderItem->getId(), $items)) {
                $quantityInvoice = (float)$items[$orderItem->getId()];
                $delta = $quantityInvoice -
                                            (float)$orderItem->getQtyInvoiced()
                                            +(float)$orderItem->getQtyCanceled()
                                            +(float)$orderItem->getQtyRefunded();
                if ($delta <= 0) {
                    unset($items[$orderItem->getId()]);
                } else {
                    $items[$orderItem->getId()] = $delta;
                }

            }
        }
        $this->needInvoiceItems = $items;
        return $items;
    }

    /**
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @param array $items
     * @return array
     */
    public function removeShippedItems(\Magento\Sales\Api\Data\OrderInterface $order, array &$items)
    {
        foreach ($order->getItems() as $orderItem) {
            if (array_key_exists($orderItem->getId(), $items)) {
                $quantityShip = (float)$items[$orderItem->getId()];
                $delta = $quantityShip -
                    (float)$orderItem->getQtyShipped()
                    +(float)$orderItem->getQtyCanceled()
                    +(float)$orderItem->getQtyRefunded();
                if ($delta <= 0) {
                    unset($items[$orderItem->getId()]);
                } else {
                    $items[$orderItem->getId()] = $delta;
                }

            }
        }
        $this->needShipmentItems = $items;
        return $items;
    }

    public function canInvoiceItems(\Magento\Sales\Api\Data\OrderInterface $order, array $items)
    {
        foreach ($order->getItems() as $orderItem) {
            if (array_key_exists($orderItem->getId(), $items)) {
                $quantity = $items[$orderItem->getId()];
                if ($orderItem->getQtyToInvoice() < $quantity) {
                    throw new \LogicException(__('Cannot create invoice for this item:'.$orderItem->getId()));
                }
                unset($items[$orderItem->getId()]);
            }
        }
        if (count($items)) {
            throw new \LogicException(__('Cannot create invoice for this items:'.json_encode($items)));
        }
    }
}
