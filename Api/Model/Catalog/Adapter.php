<?php

namespace RetailOps\Api\Model\Catalog;

use Magento\Catalog\Model\Product;

abstract class Adapter implements AdapterInterface
{
    /**
     * Will be called before preparing the data.
     *
     * @return $this
     */
    public function beforeDataPrepare()
    {
        return $this;
    }

    /**
     * Prepares the data before importing.
     *
     * @param array $data
     * @return $this
     */
    public function prepareData(array &$data)
    {
        return $this;
    }

    /**
     * Will be called when all data has been prepared.
     *
     * @return $this
     */
    public function afterDataPrepare()
    {
        return $this;
    }

    /**
     * Validates the data before importing.
     *
     * @param array $data
     * @return $this
     */
    public function validateData(array &$data)
    {
        return $this;
    }

    /**
     * @return $this
     */
    public function beforeDataProcess()
    {
        return $this;
    }

    /**
     * Processes the actual data.
     *
     * @param array $productData
     * @param Product $product
     * @return mixed
     */
    abstract public function processData(array &$productData, Product $product);

    /**
     * @param array $skuToIdMap
     * @return $this
     */
    public function afterDataProcess(array &$skuToIdMap)
    {
        return $this;
    }
}
