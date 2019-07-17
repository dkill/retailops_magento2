<?php

namespace Gudtech\RetailOps\Api;

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
