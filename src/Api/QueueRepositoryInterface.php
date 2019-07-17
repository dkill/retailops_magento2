<?php

namespace Gudtech\RetailOps\Api;

use Gudtech\RetailOps\Model\QueueInterface;
use Magento\Framework\Api\SearchCriteriaInterface;

/**
 * Queue repository interface
 */
interface QueueRepositoryInterface
{
    public function save(QueueInterface $page);

    public function getById($id);

    public function getList(SearchCriteriaInterface $criteria);

    public function delete(QueueInterface $page);

    public function deleteById($id);
}
