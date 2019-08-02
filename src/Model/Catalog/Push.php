<?php

namespace Gudtech\RetailOps\Model\Catalog;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\ProductFactory;
use Magento\Framework\App\Area;
use Magento\Store\Model\App\Emulation;
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
     * @var Emulation
     */
    private $emulation;

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
        ProductFactory $productFactory,
        Emulation $emulation
    ) {
        $this->productRepository = $productRepository;
        $this->productFactory = $productFactory;
        $this->emulation = $emulation;

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
     * @param array $productData
     * @return $this
     */
    public function prepareData(array &$productData)
    {
        foreach ($this->getDataAdapters() as $dataAdapter) {
            $dataAdapter->prepareData($productData);
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
     * @param array $productData
     * @return $this
     */
    public function validateData(array $productData)
    {
        foreach ($this->getDataAdapters() as $dataAdapter) {
            $dataAdapter->validateData($productData);
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
     * @param array $productData
     * @return bool
     * @throws Exception
     */
    public function processData($productData)
    {
        $this->emulation->startEnvironmentEmulation(0, Area::AREA_ADMINHTML);

        $product = $this->productFactory->create();

        if ($productId = $product->getIdBySku($productData['General']['SKU'])) {
            $product = $this->productRepository->getById($productId);
        }

        foreach ($this->getDataAdapters() as $dataAdapter) {
            $dataAdapter->processData($productData, $product);
        }

        if ($product->getOptions()) {
            $product->getOptionInstance()->unsetOptions();
        }

        $this->productRepository->save(($product));
        $product->clearInstance();

        $this->emulation->stopEnvironmentEmulation();

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