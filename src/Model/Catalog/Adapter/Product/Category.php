<?php

namespace Gudtech\RetailOps\Model\Catalog\Adapter\Product;

use Magento\Catalog\Model\CategoryFactory;
use Magento\Catalog\Model\CategoryRepository;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Category\Collection as CategoryCollection;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Gudtech\RetailOps\Model\Catalog\Adapter;

class Category extends Adapter
{
    private $categories;

    /**
     * Array of already processed category indexes to avoid double save
     * @var array
     */
    private $processedCategories = [];

    private $categoryCollection;
    private $categoryRepository;
    private $categoryFactory;

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
     * @param Product $product
     * @return mixed|void
     */
    public function processData(array &$productData, Product $product)
    {
        $assignedCategories = [];

        if (isset($productData['categories'])) {
            foreach ($productData['categories'] as $categoriesData) {
                $categoryPath = [];
                foreach ($categoriesData as $categoryData) {
                    $parentCategoryPath = $categoryPath;
                    $categoryPath[] = $categoryData['name'];
                    $index = $this->getCategoryPathIndex($categoryPath);
                    if (!in_array($index, $this->processedCategories)) {
                        if (!isset($this->categories[$index])) {
                            $parentIndex = $this->getCategoryPathIndex($parentCategoryPath);
                            $parentId = isset($this->categories[$parentIndex]) ? $this->categories[$parentIndex] : 1;

                            $category = $this->categoryFactory->create($categoryData);
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
                    if (!empty($categoryData['link'])) {
                        $categoryId = $this->categories[$index];
                        $assignedCategories[] = $categoryId;
                    }
                }
            }
            if (empty($productData['unset_other_categories']) || !$productData['unset_other_categories']) {
                $assignedCategories = array_merge($assignedCategories, $product->getCategoryIds());
            }
            $productData['category_ids'] = $assignedCategories;
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
