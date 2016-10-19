<?php
/**
 * Created by PhpStorm.
 * User: galillei
 * Date: 8.9.16
 * Time: 17.39
 */

namespace RetailOps\Api\Api;


interface InventoryInterface
{
    /**
     * @param string|integer $productId
     * @return null
     */
    public function setUPC($sku);

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
    public function getUPC();

}