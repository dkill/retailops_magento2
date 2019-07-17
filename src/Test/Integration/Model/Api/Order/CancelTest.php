<?php

namespace Gudtech\RetailOps\Test\Integration\Model\Api\Order;

use Magento\TestFramework\Helper\Bootstrap;

/**
 * Cancel test class
 */
class CancelTest extends \PHPUnit_Framework_TestCase
{
    const INCREMENT_1 = '100000001';

    protected $postData = [
        'channel_order_refnum' => 'xxxxxxxxxxxx',
        'grand_total' => 'xxxxxx',
        'retailops_order_id' =>8375687,
        'shipment'

    ];
    protected function setUp()
    {
        Bootstrap::getObjectManager()->get(\Magento\Framework\App\AreaList::class)
            ->getArea('adminhtml')
            ->load(\Magento\Framework\App\Area::PART_CONFIG);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testCancel()
    {
        $objectManager = Bootstrap::getObjectManager();
        /**
         * @var \Gudtech\RetailOps\Model\Api\Order\Cancel $orderCancel
         */
        $orderCancel = $objectManager->create(\Gudtech\RetailOps\Model\Api\Order\Cancel::class);
        $orderCancel->cancel(['channel_order_refnum'=>self::INCREMENT_1]);
        $order = $objectManager->get(\Magento\Sales\Model\Order::class);
        $order->loadByIncrementId(self::INCREMENT_1);
        $this->assertEquals('canceled', $order->getStatus());
        foreach ($order->getItems() as $item) {
            $this->assertEquals($item->getQtyOrdered(), $item->getQtyCanceled());
        }
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @magentoDataFixture Magento/Sales/_files/invoice.php
     */
    public function testCancelRefund()
    {
        $objectManager = Bootstrap::getObjectManager();
        /**
         * @var \Gudtech\RetailOps\Model\Api\Order\Cancel $orderCancel
         */
        $orderCancel = $objectManager->create(\Gudtech\RetailOps\Model\Api\Order\Cancel::class);
        $orderCancel->cancel(['channel_order_refnum'=>self::INCREMENT_1]);
        $order = $objectManager->get(\Magento\Sales\Model\Order::class);
        $order->loadByIncrementId(self::INCREMENT_1);
        $this->assertEquals('complete', $order->getStatus());
        foreach ($order->getItems() as $item) {
            $this->assertEquals($item->getQtyOrdered(), $item->getQtyRefunded());
        }
    }
}
