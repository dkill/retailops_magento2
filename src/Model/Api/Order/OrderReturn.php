<?php

namespace Gudtech\RetailOps\Model\Api\Order;

use Gudtech\RetailOps\Api\Services\CreditMemo\CreditMemoHelperInterface;

/**
 * Return order class.
 *
 */
class OrderReturn
{
    /**
     * @var CreditMemoHelperInterface
     */
    private $creditMemoHelper;

    /**
     * OrderReturn constructor.
     *
     * @param CreditMemoHelperInterface $creditMemoHelper
     */
    public function __construct(
      CreditMemoHelperInterface $creditMemoHelper
    ) {
        $this->creditMemoHelper = $creditMemoHelper;
    }

    /**
     * @param array $data
     */
    public function returnOrder(array $data)
    {
        $this->creditMemoHelper->create()
    }
}
