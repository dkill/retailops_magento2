<?php

namespace RetailOps\Api\Model\Catalog;

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
     * @param $product
     * @return mixed
     */
    abstract public function processData(array &$productData, $product);

    /**
     * @param array $skuToIdMap
     * @return $this
     */
    public function afterDataProcess(array &$skuToIdMap)
    {
        return $this;
    }

    /**
     * Prepare data for pull api
     *
     * @param $productCollection
     * @return $this
     */
    public function prepareOutputData($productCollection)
    {
        return $this;
    }

    /**
     * Output data for pull api
     *
     * @param Mage_Catalog_Model_Product $product
     * @return array
     */
    public function outputData($product)
    {
        return [];
    }
}
