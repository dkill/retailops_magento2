<?php

namespace RetailOps\Api\Test\Integration\Model\Map;

use Magento\TestFramework\Helper\Bootstrap;

/**
 * Map order test class.
 *
 */
class OrderTest extends \PHPUnit_Framework_TestCase
{
    const INCREMENT_1 = '100000001';

    protected function setUp()
    {
        Bootstrap::getObjectManager()->get(\Magento\Framework\App\AreaList::class)
            ->getArea('adminhtml')
            ->load(\Magento\Framework\App\Area::PART_CONFIG);
    }
    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @magentoDataFixture Magento/Sales/_files/shipment.php
     * @magentoDataFixture Magento/Sales/_files/order_shipping.php
     */
    public function testPrepareOrder()
    {
        $objectManager = Bootstrap::getObjectManager();
        /**
         * @var \RetailOps\Api\Model\Api\Map\Order $roOrder
         */
        $roOrder = $objectManager->get(\RetailOps\Api\Model\Api\Map\Order::class);
        $order = $objectManager->get(\Magento\Sales\Model\Order::class);
        $order->loadByIncrementId(self::INCREMENT_1);
        $prepareOrder = $roOrder::prepareOrder($order, $roOrder);
        $this->assertEquals(15, $prepareOrder['currency_values']['shipping_amt']);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/creditmemo.php
     */
    public function testPrepareOrderRefund()
    {
        $objectManager = Bootstrap::getObjectManager();
        /**
         * @var \RetailOps\Api\Model\Api\Map\Order $roOrder
         */
        $roOrder = $objectManager->get(\RetailOps\Api\Model\Api\Map\Order::class);
        $order = $objectManager->get(\Magento\Sales\Model\Order::class);
        $order->loadByIncrementId(self::INCREMENT_1);
        $prepareOrder = $roOrder::prepareOrder($order, $roOrder);
        $this->assertEquals(50, $prepareOrder['payment_transactions'][0]['amount']);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testPrepareOrderWithoutRefund()
    {
        $objectManager = Bootstrap::getObjectManager();
        /**
         * @var \RetailOps\Api\Model\Api\Map\Order $roOrder
         */
        $roOrder = $objectManager->get(\RetailOps\Api\Model\Api\Map\Order::class);
        $order = $objectManager->get(\Magento\Sales\Model\Order::class);
        $order->loadByIncrementId(self::INCREMENT_1);
        $prepareOrder = $roOrder::prepareOrder($order, $roOrder);
        $this->assertEquals(100, $prepareOrder['payment_transactions'][0]['amount']);
    }
}
