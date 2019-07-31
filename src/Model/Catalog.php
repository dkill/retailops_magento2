<?php

namespace Gudtech\RetailOps\Model;

use Gudtech\RetailOps\Model\Catalog\AdapterInterface;

class Catalog
{
    /**
     * Array of data adapters.
     *
     * @var array
     */
    protected $dataAdapters = [];

    public function __construct($dataAdapters = [])
    {
        $this->dataAdapters = $dataAdapters;
    }

    /**
     * @param $code
     * @return bool
     */
    public function getDataAdapter($code)
    {
        if (isset($this->dataAdapters[$code])) {
            return $this->dataAdapters[$code];
        }

        return false;
    }

    /**
     * @return array
     */
    public function getDataAdapters()
    {
        return $this->dataAdapters;
    }

    /**
     * @param $code
     * @param $adapter
     */
    public function addDataAdapter($code, $adapter)
    {
        if (!($adapter instanceof AdapterInterface)) {
            $this->_fault('wrong_data_adapter', 'Wrong data adapter class');
        }

        $this->dataAdapters[$code] = $adapter;
    }
}