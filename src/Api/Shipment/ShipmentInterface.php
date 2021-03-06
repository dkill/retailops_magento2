<?php

namespace Gudtech\RetailOps\Api\Shipment;

/**
 * Shipment interface
 *
 */
interface ShipmentInterface
{
    /**
     * @param array $postData
     * @return void
     */
    public function setUnShippedItems(array $postData = []);

    /**
     * @param array $postData
     * @return void
     */
    public function setTrackingAndShipmentItems(array $postData = []);

    /**
     * @param array $packageItems
     * @return void
     */
    public function setShipmentsItems(array $packageItems = []);
    
    /**
     * @param array $postData
     * @return void
     */
    public function registerShipment(array $postData = []);
}
