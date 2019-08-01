<?php

namespace Gudtech\RetailOps\Model\Catalog;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\ProductFactory;
use Gudtech\RetailOps\Model\Catalog;

class Push extends Catalog
{
    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var ProductFactory
     */
    private $productFactory;

    /**
     * Catalog push constructor.
     *
     * @param array $dataAdapters
     * @param ProductRepositoryInterface $productRepository
     * @param ProductFactory $productFactory
     */
    public function __construct(
        $dataAdapters = [],
        ProductRepositoryInterface $productRepository,
        ProductFactory $productFactory
    ) {
        $this->productRepository = $productRepository;
        $this->productFactory = $productFactory;

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
        $product = $this->productFactory->create();

        if ($productId = $product->getIdBySku($data['General']['SKU'])) {
            $product = $this->productRepository->getById($productId);
        }

        foreach ($this->getDataAdapters() as $dataAdapter) {
            $dataAdapter->processData($data, $product);
        }

        if ($product->getOptions()) {
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
            $dataAdapter->afterDataProcess();
        }

        return $this;
    }
}