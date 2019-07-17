<?php

namespace Gudtech\RetailOps\Model;

/**
 * Inventory class.
 *
 */
class Inventory implements \Gudtech\RetailOps\Api\InventoryInterface
{
    protected $sku;

    protected $count;

    protected $realCount;

    protected $reserveCount;

    public function setRealCount($realCount)
    {
        $this->realCount = $realCount;
    }

    public function getRealCount()
    {
        return $this->realCount;
    }

    public function setReserveCount($reserveCount)
    {
        $this->reserveCount = $reserveCount;
    }

    public function getReserveCount()
    {
        return $this->reserveCount;
    }

    public function setUPC($sku)
    {
        $this->sku = $sku;
    }

    public function setCount($count)
    {
        $this->count = $count;
    }

    public function getUPC()
    {
        return $this->sku;
    }

    public function getCount()
    {
        return $this->count;
    }
}
