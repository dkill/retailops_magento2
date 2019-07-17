<?php

namespace Gudtech\RetailOps\Api;

/**
 * Inventory interface
 *
 */
interface InventoryInterface
{
    /**
     * @param string|integer $sku
     * @return null
     */
    public function setUpc($sku);

    /**
     * @param string|integer $count
     * @return null
     */
    public function setCount($count);

    /**
     * @return string|null|integer
     */

    public function getCount();

    /**
     * @return string|null|integer
     */
    public function getUpc();

    /**
     * @param string|integer|float $realCount
     * @return mixed
     */
    public function setRealCount($realCount);

    /**
     * @return mixed
     */
    public function getRealCount();

    /**
     * @param float $reserveCount
     * @return mixed
     */
    public function setReserveCount($reserveCount);

    /**
     * @return mixed
     */
    public function getReserveCount();
}
