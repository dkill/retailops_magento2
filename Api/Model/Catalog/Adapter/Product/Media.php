<?php

namespace RetailOps\Api\Model\Catalog\Adapter\Product;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Gallery\Processor as ProductMediaGalleryProcessor;
use Magento\Catalog\Model\ProductRepository;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\Exception\RuntimeException;
use Magento\Framework\Filesystem\DirectoryList;
use RetailOps\Api\Model\Catalog\Adapter;
use Magento\Framework\HTTP\ClientFactory as HttpClientFactory;
use Magento\Framework\Filesystem\Io\File;

class Media extends Adapter
{
    private $mediaDataToSave = [];

    private $productRepository;
    private $productMediaGalleryProcessor;
    private $directoryList;
    private $httpClientFactory;
    private $fileSystem;

    /**
     * Media constructor.
     *
     * @param ProductRepository $productRepository
     * @param ProductMediaGalleryProcessor $productMediaGalleryProcessor
     * @param DirectoryList $directoryList
     * @param HttpClientFactory $httpClientFactory
     * @param File $fileSystem
     */
    public function __construct(
        ProductRepository $productRepository,
        ProductMediaGalleryProcessor $productMediaGalleryProcessor,
        DirectoryList $directoryList,
        HttpClientFactory $httpClientFactory,
        File $fileSystem
    ) {
        $this->productRepository = $productRepository;
        $this->productMediaGalleryProcessor = $productMediaGalleryProcessor;
        $this->directoryList = $directoryList;
        $this->httpClientFactory = $httpClientFactory;
        $this->fileSystem = $fileSystem;
    }

    /**
     * @param array $productData
     * @param Product $product
     * @return mixed|void
     */
    public function processData(array &$productData, Product $product)
    {
        if ($product->getId() && (!isset($productData['unset_other_media']) || $productData['unset_other_media'])) {
            $this->clearProductGallery($product);
        }

        if (isset($productData['media'])) {
            $this->mediaDataToSave[$productData['sku']] = json_encode($productData['media']);
        }

        if (isset($productData['straight_media_process']) && $productData['straight_media_process']) {
            $this->straightMediaProcessing = true;
        }
    }

    /**
     * @param array $skuToIdMap
     * @return $this|void
     */
    public function afterDataProcess(array &$skuToIdMap)
    {
        if ($this->mediaDataToSave) {
            foreach ($this->mediaDataToSave as $sku => $data) {
                $productId = $skuToIdMap[$sku];
                $this->downloadProductImages($productId, $data);
            }
        }
    }

    /**
     * Unset product image gallery
     *
     * @param Product $product
     */
    public function clearProductGallery(Product $product)
    {
        $mediaGalleryItems = $product->getMediaGalleryEntries();

        foreach ($mediaGalleryItems as $key => $item) {
            $this->productMediaGalleryProcessor->removeImage($product, $item->getFile());
            unset($mediaGalleryItems[$key]);
        }

        $product->setMediaGalleryEntries($mediaGalleryItems);
        $this->productRepository->save($product);
    }

    /**
     * Download products media
     *
     * @param int $productId
     * @param array $data
     * @return array
     */
    public function downloadProductImages($productId, $data)
    {
        $tmpDirectory = $this->directoryList->getPath('var') . DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR . uniqid();
        $this->fileSystem->checkAndCreateFolder($tmpDirectory);
        $this->fileSystem->open(['path' => $tmpDirectory]);
        $maximumNumberOfTries = 3;

        $productId = $item->getProductId();
        $data = json_decode($item->getMediaData(), true);

        $product = $this->productRepository->getById($productId);
        $product->setStoreId(0);

        foreach ($data as $productImage) {
            $sku = $product->getSku();

            $file = $this->productImageExists($productId, $productImage);

            if (!$file) {

                $client = $this->httpClientFactory->create();
                $client->get($productImage['download_url']);

                $fileName = $this->getFileName($url, $productImage);
                $fileName = $tmpDirectory . DIRECTORY_SEPARATOR . $fileName;

                $numberOfTries = 0;
                while ($numberOfTries++ < $maximumNumberOfTries && !$response) {
                    $client->get($productImage['download_url']);
                    $response = $client->getBody();
                }

                if ($client->getStatus() == 404) {
                    throw new NotFoundException("URL not found: ". $productImage['download_url']);
                }

                if (!$respone || $client->getStatus() != 200) {
                    throw new RuntimeException("Could not process: ". $productImage['download_url']);
                }

                $this->fileSystem->write($fileName, $response);

                if (isset($productImage['types'])) {
                    $product->addImageToMediaGallery(
                        $fileName,
                        $productImage['types'],
                        true
                    );
                } else {
                    $product->addImageToMediaGallery(
                        $fileName,
                        null,
                        true
                    );
                }
            }
        }

        $this->productRepository->save($product);

        // Remove temporary directory
        $this->fileSystem->rmdir($tmpDirectory, true);
    }

    /**
     * Get existing image filename, if any, based on mediakey and filename
     *
     * @param Product $product
     * @param string $productImage
     * @return boolean
     * @throws LocalizedException
     */
    private function productImageExists($product, $productImage)
    {
        $mediaGalleryItems = $product->getMediaGalleryEntries();

        foreach ($mediaGalleryItems as $key => $item) {
            if ($this->getFileName($item->getFile()) == $this->getFileName($productImage)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the filename from a full path.
     *
     * @param string $path
     * @return string
     */
    private function getFileName($path)
    {
        $pathItems = pathinfo($path);

        return $pathItems['basename'];
    }
}
