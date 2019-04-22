<?php

namespace RetailOps\Api\Api;

interface ApiInterface
{
    /**
     * @api
     * @param   string[] $data
     * @return string
     */
    public function pushInventory($data);
}
