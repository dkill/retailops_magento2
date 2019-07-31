<?php

namespace Gudtech\RetailOps\Model\Catalog;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Gudtech\RetailOps\Model\Catalog;

class Push extends Catalog
{
    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * Catalog push constructor.
     *
     * @param array $dataAdapters
     * @param ProductRepositoryInterface $productRepository
     */
    public function __construct($dataAdapters = [], ProductRepositoryInterface $productRepository)
    {
        $this->productRepository = $productRepository;

        parent::__construct($dataAdapters);
    }

    /**
     * @return $this
     */
    public function beforeDataPrepare()
    {
        foreach ($this->getDataAdapters() as $dataAdapter) {
            $dataAdapter->beforeDataPrepare();
        }

        return $this;
    }

    /**
     * @param array $data
     * @return $this
     */
    public function prepareData(array &$data)
    {
        foreach ($this->getDataAdapters() as $dataAdapter) {
            $dataAdapter->prepareData($data);
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function afterDataPrepare()
    {
        foreach ($this->getDataAdapters() as $dataAdapter) {
            $dataAdapter->afterDataPrepare();
        }

        return $this;
    }

    /**
     * @param array $data
     * @return $this
     */
    public function validateData(array &$data)
    {
        foreach ($this->getDataAdapters() as $dataAdapter) {
            $dataAdapter->validateData($data);
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function beforeDataProcess()
    {
        foreach ($this->getDataAdapters() as $dataAdapter) {
            $dataAdapter->beforeDataProcess();
        }

        return $this;
    }

    /**
     * @param array $data
     * @return bool
     * @throws Exception
     */
    public function processData(array &$data)
    {
        $product = $this->productRepository->get($data['sku']);

        foreach ($this->getDataAdapters() as $dataAdapter) {
            $dataAdapter->processData($data, $product);
        }

        //$this->_skuToIdMap[$data['sku']] = $product->getId();
        if ($product->getHasOptions()) {
            $product->getOptionInstance()->unsetOptions();
        }
        $product->clearInstance();

        return true;
    }

    /**
     * @return $this
     */
    public function afterDataProcess()
    {
        foreach ($this->getDataAdapters() as $dataAdapter) {
            //$dataAdapter->afterDataProcess();
        }

        return $this;
    }
}