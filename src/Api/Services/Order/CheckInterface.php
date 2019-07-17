<?php

namespace Gudtech\RetailOps\Api\Services\Order;

/**
 * Order check interface
 *
 */
interface CheckInterface
{
    /**
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @return boolean
     */
    public function canInvoice(\Magento\Sales\Model\Order $order);

    /**
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @return boolean
     */
    public function canOrderShip(\Magento\Sales\Model\Order $order);

    /**
     * @param string|integer $itemId
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @return boolean
     */
    public function hasItem($itemId, \Magento\Sales\Model\Order $order);

    /**
     * @param string|integer $itemId
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @return mixed
     */
    public function itemCanShipment($itemId, \Magento\Sales\Model\Order $order);

    /**
     * @param \Magento\Sales\Model\Order $order
     * @return mixed
     */
    public function getForcedShipmentWithInvoice(\Magento\Sales\Model\Order $order);
}
