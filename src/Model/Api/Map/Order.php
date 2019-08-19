<?php

namespace Gudtech\RetailOps\Model\Api\Map;

use Magento\Framework\App\ObjectManager;
use Gudtech\RetailOps\Model\Api\Map\Order as OrderMap;
use Gudtech\RetailOps\Service\CalculateDiscountInterface;
use Gudtech\RetailOps\Service\CalculateItemPriceInterface;
use Gudtech\RetailOps\Api\Order\Map\CalculateAmountInterface;
use Magento\Sales\Api\Data\OrderAddressInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderItemInterface;

/**
 * Map order class.
 *
 */
class Order
{
    const CONFIGURABLE = 'configurable';
    const AUTH_STATUS = 'processing';

    //order pull to
    const ORDER_PULL_STATUS = 2;
    const ORDER_NO_SEND_STATUS = 0;

    /**
     * @var \Gudtech\RetailOps\Service\CalculateDiscountInterface
     */
    protected $calculateDiscount;

    /**
     * @var \Gudtech\RetailOps\Service\Order\Map\RewardPointsInterface
     */
    protected $rewardPoints;

    /**
     * Status for retailops
     * @var array $retailopsItemStatus
     * http://gudtech.github.io/retailops-sdk/v1/channel/#!/default/post_order_pull_v1
     */
    public static $retailopsItemStatus = ['ship', 'advisory', 'instore'];

    /**
     * @var array $paymentProcessingType
     * from http://gudtech.github.io/retailops-sdk/v1/channel/#!/default/post_order_pull_v1
     */
    public static $paymentProcessingType = [
        'default' => 'channel_payment',
        'reward' => 'channel_storecredit',
        'gift' => 'channel_giftcert',
        'authorized' => 'authorize.net'
    ];

    /**
     * @var \Gudtech\RetailOps\Api\Order\Map\CalculateAmountInterface
     */
    public $calculateAmount;

    /**
     * Order map constructor.
     *
     * @param CalculateDiscountInterface $calculateDiscount
     * @param CalculateItemPriceInterface $calculateItemPrice
     * @param CalculateAmountInterface $calculateAmount
     */
    public function __construct(
        CalculateDiscountInterface $calculateDiscount,
        CalculateItemPriceInterface $calculateItemPrice,
        CalculateAmountInterface $calculateAmount
    ) {
        $this->calculateDiscount = $calculateDiscount;
        $this->calculateItemPrice = $calculateItemPrice;
        $this->calculateAmount = $calculateAmount;
    }

    /**
     * @param \Magento\Sales\Api\Data\OrderInterface[] $orders
     * @return array
     */
    public function getOrders($orders)
    {
        if (count($orders)) {
            $prepareOrders = [];
            /**
             * @var \Magento\Sales\Api\Data\OrderInterface $order
             */
            foreach ($orders as $order) {
                $prepareOrders[] = Order::prepareOrder($order, $this);
                $order->setData('retailops_send_status', OrderMap::ORDER_PULL_STATUS);
                $order->save();
            }

            return $prepareOrders;
        }
        return [];
    }

    /**
     * @param $order
     * @param Order $instance
     * @return mixed
     */
    public static function prepareOrder(\Magento\Sales\Api\Data\OrderInterface $order, $instance)
    {
        $prepareOrder = [];
        $prepareOrder['channel_order_refnum'] = $order->getIncrementId();
        $prepareOrder['currency_code'] = $order->getOrderCurrencyCode();
        $prepareOrder['currency_values'] = $instance->getCurrencyValues($order);
        $prepareOrder['channel_date_created'] = (new \DateTime($order->getCreatedAt(), new \DateTimeZone('UTC')))
            ->format('c');
        $prepareOrder['billing_address'] = $instance->getAddress($order, $order->getBillingAddress());
        $prepareOrder['shipping_address'] = $instance->getAddress($order, $order->getShippingAddress());
        $prepareOrder['order_items'] = $instance->getOrderItems($order);
        $prepareOrder['ship_service_code'] = $order->getShippingMethod();
        //add gift message if available
        if ($order->getGiftMessageAvailable()) {
            $giftHelper = ObjectManager::getInstance()->get(\Magento\GiftMessage\Helper\Message::class);
            $message = $giftHelper->getGiftMessage($order->getGiftMessageId());
            $prepareOrder['gift_message'] = $message;
        }
        //@todo how send orders with coupon code and gift cart
        $prepareOrder['payment_transactions'] = $instance->getPaymentTransactions($order);
        $prepareOrder['customer_info'] = $instance->getCustomerInfo($order);
        $prepareOrder['ip_address'] = $order->getRemoteIp();
        return $instance->clearNullValues($prepareOrder);
    }

    private function getCurrencyValues($order)
    {
        $values = [];
        $values['shipping_amt'] = $this->calculateAmount->calculateShipping($order);
        // $values['tax_amt'] = (float)$order->getTaxAmount();
        $values['discount_amt'] = $this->calculateDiscount->calculate($order);
        return $values;
    }

    /**
     * @param OrderInterface $order
     * @param OrderAddressInterface $orderAddress
     * @return array
     */
    private function getAddress($order, $orderAddress)
    {
        $address = [];
        $address['first_name'] = $orderAddress->getFirstname();
        $address['last_name'] = $orderAddress->getLastname();
        $address['address1'] = is_array($orderAddress->getStreet()) ? $orderAddress->getStreet()[0] : $orderAddress->getStreet();
        if (is_array($orderAddress->getStreet()) && count($orderAddress->getStreet()) > 1) {
            $address['address2'] = $orderAddress->getStreet()[1];
        }
        $address['city'] = $orderAddress->getCity();
        $address['postal_code'] = $orderAddress->getPostcode();
        $address['state_match'] = $orderAddress->getRegion();
        $address['country_match'] = $orderAddress->getCountryId();
        $address['company'] = $orderAddress->getCompany();

        return $address;
    }

    /**
     * @param  \Magento\Sales\Model\Order $order
     * @return array
     */
    public function getOrderItems($order)
    {
        $items = [];
        $item = [];
        $orderItems = $order->getItems();
        foreach ($orderItems as $orderItem) {

            if ($orderItem->getParentItem()) {
                continue;
            }

            $item['channel_item_refnum'] = $orderItem->getId();
            $item['sku'] = $orderItem->getSku();
            $item['sku_description'] = $orderItem->getName();
            $item['item_type'] = $this->getItemType($orderItem);
            $item['currency_values'] = $this->getItemCurrencyValues($orderItem);
            $item['quantity'] = $this->getQuantity($orderItem);

            $items[] = $item;
        }
        return $items;
    }

    /**
     * @param OrderItemInterface $item
     * @return int
     */
    private function getQuantity($item)
    {
        if ($item->getParentItem()) {
            $item = $item->getParentItem();
        }

        $qty = $item->getQtyOrdered() - $item->getQtyRefunded() - $item->getQtyCanceled();

        return (int)$qty;
    }

    /**
     * @param OrderItemInterface $item
     * @return string
     */
    private function getItemType($item)
    {
        //@todo after design shipping with retaiops add logic for orders
        return 'ship';
    }

    /**
     * @param $item
     * @return array
     */
    public function getItemCurrencyValues($item)
    {
        $itemCurrency = [];
        if ($item->getParentItem()) {
            $item = $item->getParentItem();
        }
        /** before RO fix discount error
        $itemCurrency['discount_amt'] = (float)$item->getDiscountAmount();
        $itemCurrency['discount_pct'] = (float)$item->getDiscountPercent();
        $itemCurrency['unit_price'] = (float)$item->getBasePrice();
         **/
        //calculate items price before RO fix discount error
        $itemCurrency['unit_price'] = $this->calculateItemPrice->calculate($item);
        $itemCurrency['unit_tax'] = $this->calculateItemPrice->calculateItemTax($item);

        return $itemCurrency;
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @return array
     */
    public function getPaymentTransactions($order)
    {
        $paymentMethod = $order->getPayment();
        $paymentTransactions = [];
        $payment = [];
        $storeCredit = [];

        $payment['payment_processing_type'] = self::$paymentProcessingType['default'];
        $payment['payment_type'] = $paymentMethod->getMethod();
        $payment['amount'] = $this->calculateAmount->calculateGrandTotal($order);
        $payment['transaction_type'] = 'charge';
        $paymentTransactions[] = $payment;

        if ($order->getBaseCustomerBalanceAmount()) {
            $storeCredit['payment_processing_type'] = self::$paymentProcessingType['reward'];
            $storeCredit['payment_type'] = 'Store Credit';
            $storeCredit['amount'] = $order->getBaseCustomerBalanceAmount();
            $storeCredit['transaction_type'] = 'charge';
            $paymentTransactions[] = $storeCredit;
        }

        return $this->getGiftPaymentTransaction($paymentTransactions, $order);
    }

    /**
     * @param array $payments
     * @param  \Magento\Sales\Model\Order $order
     * @return array
     */
    public function getGiftPaymentTransaction(array $payments, $order)
    {
        if ($order->getGiftCardsAmount() > 0) {
            $paymentG = [];
            $paymentG['payment_type'] = 'gift';
            $paymentG['payment_processing_type'] = self::$paymentProcessingType['gift'];
            $paymentG['amount'] = (float)$order->getBaseGiftCardsAmount();
            $payments[] = $paymentG;
        }

        return $payments;
    }

    /**
     * @param  \Magento\Sales\Model\Order $order
     * @return array
     */
    private function getCustomerInfo($order)
    {
        $customer = [];
        $customer['email_address'] = $order->getCustomerEmail();
        if ($order->getCustomerIsGuest()) {
            $customer['full_name'] = 'Guest';
        } else {
            $customer['full_name'] = $order->getCustomerFirstname() . ' ' . $order->getCustomerLastname();
        }

        return $customer;
    }

    /**
     * @param  array $orders
     * @return mixed
     */
    public function clearNullValues(&$orders)
    {
        foreach ($orders as $key => &$order) {
            if (is_array($order)) {
                $this->clearNullValues($order);
            }
            if ($order === null) {
                unset($orders[$key]);
            }
        }
        return $orders;
    }
}
