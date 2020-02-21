<?php

namespace Gudtech\RetailOps\Model\Api\Order;

use Gudtech\RetailOps\Api\Services\CreditMemo\CreditMemoHelperInterface;
use Magento\Sales\Model\OrderFactory;

/**
 * Return order class.
 *
 */
class Returned
{
    /**
     * @var string
     */
    const REFUND_TYPE_STORE_CREDIT = 'storecredit';

    /**
     * @var CreditMemoHelperInterface
     */
    private $creditMemoHelper;

    /**
     * @var OrderFactory
     */
    private $orderFactory;

    /**
     * OrderReturn constructor.
     *
     * @param CreditMemoHelperInterface $creditMemoHelper
     */
    public function __construct(
        OrderFactory $orderFactory,
        CreditMemoHelperInterface $creditMemoHelper
    ) {
        $this->orderFactory = $orderFactory;
        $this->creditMemoHelper = $creditMemoHelper;
    }

    /**
     * @param array $postData
     */
    public function returnOrder($orderId, array $postData)
    {
        $order = $this->orderFactory->create()->loadByIncrementId($orderId);
        $items = [];

        foreach ($postData['items'] as $returnItem) {
            foreach ($order->getItems() as $orderItem) {
                if ($orderItem->getSku() == $returnItem['sku'] && !$orderItem->getParentItemId()) {
                    $items[$orderItem->getId()] = (float) $returnItem['quantity'];
                }
            }
        }

        if (count($items)) {
            if ($postData['refund_action'] == self::REFUND_TYPE_STORE_CREDIT) {
                $this->creditMemoHelper->setRefundCustomerbalanceReturnEnable(1);
                $this->creditMemoHelper->setRefundCustomerbalanceReturnAmount(($postData['refund_amt'] + $postData['shipping_amt']));
            }

            $this->creditMemoHelper->setShippingAmount($postData['shipping_amt']);
            $this->creditMemoHelper->create($order, $items);
        }
    }
}
