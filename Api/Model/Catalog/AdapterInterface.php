<?php

namespace RetailOps\Api\Model\Catalog;

use Magento\Catalog\Model\Product;

interface AdapterInterface
{
    /**
     * Will be called before preparing the data.
     *
     * @return $this
     */
    public function beforeDataPrepare();

    /**
     * Prepares the data before importing.
     *
     * @param array $data
     * @return $this
     */
    public function prepareData(array &$data);

    /**
     * Will be called when all data has been prepared.
     *
     * @return $this
     */
    public function afterDataPrepare();

    /**
     * Validates the data before importing.
     *
     * @param array $data
     * @return $this
     */
    public function validateData(array &$data);

    /**
     * Will be called before data will be processed.
     *
     * @return $this
     */
    public function beforeDataProcess();

    /**
     * Processes the actual data.
     *
     * @param array $productData
     * @param $product
     * @return mixed
     */
    public function processData(array &$productData, $product);

    /**
     * Will be called after data has been processed.
     *
     * @param array $skuToIdMap
     * @return $this
     */
    public function afterDataProcess(array &$skuToIdMap);
}