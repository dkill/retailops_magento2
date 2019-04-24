<?php

namespace RetailOps\Api\Api;

/**
 * API interface
 *
 */
interface ApiInterface
{
    /**
     * @api
     * @param   string[] $data
     * @return string
     */
    public function pushInventory($data);
}
