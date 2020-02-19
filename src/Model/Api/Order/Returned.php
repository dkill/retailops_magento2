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
                if ($orderItem->getSku() == $returnItem['sku']) {
                    $items[$orderItem->getId()] = (float) $returnItem['quantity'];
                }
            }
        }

        if (count($items)) {
            return $this->creditMemoHelper->create($order, $items);
        }
    }
}
