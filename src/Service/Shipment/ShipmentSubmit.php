<?php

namespace Gudtech\RetailOps\Service\Shipment;

/**
 * Submit shipment service class.
 *
 */
class ShipmentSubmit extends \Gudtech\RetailOps\Service\Shipment
{
    /**
     * @var \Gudtech\RetailOps\Service\OrderCheck
     */
    protected $orderCheck;

    /**
     * @var \Gudtech\RetailOps\Service\InvoiceHelper
     */
    protected $invoiceHelper;

    /**
     * ShipmentSubmit constructor.
     * @param \Magento\Shipping\Model\Config $shippingConfig
     * @param \Magento\Shipping\Controller\Adminhtml\Order\ShipmentLoader $shipmentLoader
     * @param \Magento\Sales\Model\Order\Email\Sender\ShipmentSender $shipmentSender
     * @param \Gudtech\RetailOps\Service\OrderCheck $orderCheck
     * @param \Gudtech\RetailOps\Service\InvoiceHelper $invoiceHelper
     */
    public function __construct(
        \Magento\Shipping\Model\Config $shippingConfig,
        \Magento\Shipping\Controller\Adminhtml\Order\ShipmentLoader $shipmentLoader,
        \Magento\Sales\Model\Order\Email\Sender\ShipmentSender $shipmentSender,
        \Gudtech\RetailOps\Service\OrderCheck $orderCheck,
        \Gudtech\RetailOps\Service\InvoiceHelper $invoiceHelper
    ) {
        $this->orderCheck = $orderCheck;
        $this->invoiceHelper = $invoiceHelper;
        parent::__construct($shippingConfig, $shipmentLoader, $shipmentSender);
    }

    protected function issetItems($items, \Magento\Sales\Api\Data\OrderInterface $order)
    {
        if (is_array($items) && count($items)) {
            foreach ($items as $item => $qty) {
                if (!$this->orderCheck->hasItem($item, $order)) {
                    throw new \LogicException(__(sprintf('Item with such id:%s don\'t exists in  order:%s'), [$item, $order->getId()]));
                }
            }
            return true;
        }
        throw new \LogicException(__('No have any items for shipment'));
    }

    public function setTrackingAndShipmentItems(array $postData = [])
    {
        if (!isset($postData['shipment'])) {
            return;
        }
        $shipment = $postData['shipment'];
        if (!count($shipment)) {
            return;
        }

        if (!isset($shipment['packages'])) {
            throw new \LogicException(__('No any package for orders'));
        }

        $tracking = [];
        $magentoTracking = $this->_getCarriersInstances();

        foreach ($shipment['packages'] as $package) {
            $carrierName = isset($package['carrier_name']) ? strtolower($package['carrier_name']) : null;
            if ($carrierName === null) {
                continue;
            }
            if (isset($package['tracking_number']) && !empty($package['tracking_number'])) {
                //try to find retailops carrier in magento carriers, else use custom label
                if (isset($magentoTracking[$carrierName])) {
                    $tracking[] = [
                        'carrier_code' => $carrierName,
                        'title' => $magentoTracking[$carrierName]->getConfigData('title'),
                        'number' => isset($package['tracking_number']) ? $package['tracking_number'] : null
                    ];
                } else {
                    $tracking[] = [
                        'carrier_code' => 'custom',
                        'title' => $package['carrier_class_name'] ?? 'RetailOps',
                        'number' => isset($package['tracking_number']) ? $package['tracking_number'] : null
                    ];
                }
            }
            $this->setShipmentsItems($package['package_items']);
            $this->tracking = $tracking;
        }
    }

    protected function haveQuantityToShip($items, \Magento\Sales\Api\Data\OrderInterface $order)
    {
        foreach ($items as $itemId => $quantity) {
            if (!$this->orderCheck->itemCanShipment($itemId, $order)) {
                throw new \LogicException(__(sprintf('Item id:%s can\'t be shipped', $itemId)));
            }
        }
        return true;
    }
}
