<?php

namespace Gudtech\RetailOps\Model\Catalog\Adapter\Product;

use Magento\Catalog\Model\CategoryFactory;
use Magento\Catalog\Model\CategoryRepository;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Category\Collection as CategoryCollection;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Gudtech\RetailOps\Model\Catalog\Adapter;
use phpDocumentor\Reflection\Types\Array_;
use tests\unit\Util\TestDataArrayBuilder;

class Category extends Adapter
{
    /**
     * Default category settings to create a new category with.
     *
     * @var array
     */
    const CATEGORY_DEFAULT_SETTINGS = [
        'is_anchor' => true,
        'is_active' => true,
        'available_sort_by' => 'new_from_date',
        'default_sort_by' => 'new_from_date'
    ];

    /**
     * Variable which is used as delimiter for the category paths
     *
     * @var string
     */
    const CATEGORY_PATH_DELIMITER = "/";

    /**
     * @var array
     */
    private $categories;

    /**
     * Array of already processed category indexes to avoid double save
     *
     * @var array
     */
    private $processedCategories = [];

    /**
     * @var CategoryCollection
     */
    private $categoryCollection;

    /**
     * @var CategoryRepository
     */
    private $categoryRepository;

    /**
     * @var CategoryFactory
     */
    private $categoryFactory;

    /**
     * Category constructor.
     *
     * @param CategoryCollection $categoryCollection
     * @param CategoryFactory $categoryFactory
     * @param CategoryRepository $categoryRepository
     */
    public function __construct(
        CategoryCollection $categoryCollection,
        CategoryFactory $categoryFactory,
        CategoryRepository $categoryRepository
    ) {
        $this->categoryCollection = $categoryCollection;
        $this->categoryFactory = $categoryFactory;
        $this->categoryRepository = $categoryRepository;

        $this->initCategories();
    }

    /**
     * @param array $productData
     * @return $this|Adapter
     */
    public function prepareData(array &$productData)
    {
        /**
         * @todo Cleanup Feed in RetailOps so that it doesn't send over the additional data in [[]] which we are not
         * processing. Once the feed has been cleaned up, this code can be removed.
         */
        if (isset($productData['Categories']['Category'])) {
            foreach ($productData['Categories']['Category'] as &$categoryData) {
                if ($categoryData['Category Path']) {
                    $categoryData['Category Path'] = trim(
                        preg_replace("/\[\[(.*)\]\]/","", $categoryData['Category Path'])
                    );
                }
            }
        }

        return $this;
    }

    /**
     * @param array $productData
     * @param Product $product
     * @return mixed|void
     */
    public function processData(array $productData, Product $product)
    {
        $assignedCategories = [];

        if (isset($productData['Categories']['Category'])) {
            foreach ($productData['Categories']['Category'] as $categoryData) {
                if ($categoryData['Category Path']) {

                    $categories = explode("|",  $categoryData['Category Path']);

                    foreach ($categories as $category) {

                        $categoryPath = [];
                        $categoryPaths = explode(self::CATEGORY_PATH_DELIMITER, $category);

                        foreach ($categoryPaths as $categoryName) {

                            $categoryName = trim($categoryName);
                            $parentCategoryPath = $categoryPath;

                            if ($categoryName) {
                                $categoryPath[] = $categoryName;
                                $index = $this->getCategoryPathIndex($categoryPath);

                                if (!in_array($index, $this->processedCategories)) {
                                    if (!isset($this->categories[$index])) {
                                        $parentIndex = $this->getCategoryPathIndex($parentCategoryPath);
                                        $parentId = isset($this->categories[$parentIndex]) ? $this->categories[$parentIndex] : 1;

                                        $category = $this->categoryFactory->create(self::CATEGORY_DEFAULT_SETTINGS);
                                        $category->setName($categoryName);
                                        $category->setParentId($parentId);
                                        $category->setIsActive(true);
                                        $category = $this->categoryRepository->save($category);

                                        $this->categories[$index] = $category->getId();
                                    } else {
                                        $categoryId = $this->categories[$index];

                                        $category = $this->categoryRepository->get($categoryId);
                                        $category->addData($categoryData);

                                        $this->categoryRepository->save($category);
                                    }
                                    $this->processedCategories[] = $index;
                                }
                            }

                            $assignedCategories[] = $this->categories[$index];
                        }
                    }
                }
            }

            $assignedCategories = array_merge($assignedCategories, $product->getCategoryIds());

            $product->setCategoryIds($assignedCategories);
        }
    }

    /**
     * Initialize categories.
     *
     * @return $this
     */
    private function initCategories()
    {
        $this->categoryCollection->addNameToResult();

        foreach ($this->categoryCollection as $category) {
            $structure = explode('/', $category->getPath());
            $pathSize  = count($structure);
            if ($pathSize > 1) {
                $path = [];
                for ($i = 1; $i < $pathSize; $i++) {
                    if ($this->categoryCollection->getItemById($structure[$i])) {
                        $path[] = $this->categoryCollection->getItemById($structure[$i])->getName();
                    }
                }
                $index = $this->getCategoryPathIndex($path);
                $this->categories[$index] = $category->getId();
            }
        }

        return $this;
    }

    /**
     * @param $path
     * @return string
     */
    private function getCategoryPathIndex($path)
    {
        return implode('/', $path);
    }
}
