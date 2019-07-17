<?php

namespace Gudtech\RetailOps\Model\Api\Traits;

use Magento\Framework\App\ObjectManager;

/**
 * Full filter trait.
 *
 */
trait FullFilter
{
    /**
     * @var \Magento\Framework\Api\SearchCriteria
     */
    protected $searchCriteria;

    /**
     * @var \Magento\Framework\Api\Filter
     */
    protected $filter;

    /**
     * @var \Magento\Framework\Api\Search\FilterGroup
     */
    protected $filterGroup;

    /**
     * @param array $filters
     * @return mixed
     */
    public function createFilterGroups(array $filters)
    {
        /**
         * @var  \Magento\Framework\Api\Search\FilterGroup
         */
        $filterGroup = ObjectManager::getInstance()->create(\Magento\Framework\Api\Search\FilterGroup::class);
        $filterGroup->setFilters($filters);
        return $filterGroup;
    }

    /**
     * @param $field
     * @param $operator
     * @param $value
     * @return \Magento\Framework\Api\Filter
     */
    public function createFilter($field, $operator, $value)
    {
        $filter = ObjectManager::getInstance()->create(\Magento\Framework\Api\Filter::class);
        $filter->setField($field)
            ->setConditionType($operator)
            ->setValue($value);
        return $filter;
    }

    protected function addFilter($name, \Magento\Framework\Api\Filter $filter)
    {
        $this->filters[$name] = $this->createFilterGroups([$filter]);
    }

    private function getFilters()
    {
        return $this->filters;
    }

    private function addFilterGroups()
    {
        $this->searchCriteria = ObjectManager::getInstance()->create(\Magento\Framework\Api\SearchCriteria::class);
        $groups = [];

        if (($filters = $this->getFilters()) && count($filters)) {
            foreach ($filters as $key => $filter) {
                $groups[] = $filter;
            }
        }
        $this->searchCriteria->setFilterGroups($groups)
            ->setSortOrders($this->createSortOrder('created_at', 'asc'));
    }

    private function createSortOrder($field, $direction)
    {
        // Create a sort order
        $sortOrder = ObjectManager::getInstance()->create(\Magento\Framework\Api\SortOrder::class);
        $sortOrder->setField($field)
            ->setDirection($direction);

        return [$sortOrder];
    }

    public function getOrderIdByIncrement($orderInc)
    {
        $orders = [];
        $orders[$orderInc] = 1;
        $ordersId = array_keys($this->setOrderIdByIncrementId($orders));
        if (!is_array($ordersId) || !count($ordersId)) {
            throw new \LogicException(__('This increment id doesn\'t exists'));
        }
        $orderId = reset($ordersId);
        return $orderId;
    }

    /**
     * @param $orders
     */
    public function setOrderIdByIncrementId($orders)
    {
        $resource = ObjectManager::getInstance()->get(\Magento\Framework\App\ResourceConnection::class);
        $connection = $resource->getConnection();
        $existsOrders = [];
        $template = 'increment_id IN (%s)';
        $orderKeys = array_keys($orders);
        array_walk($orderKeys, [$this,'addQuote']);
        $bind = join($orderKeys, ',');
        $where = sprintf($template, $bind);
        $select = $connection->select()->from('sales_order', ['entity_id', 'increment_id'])
            ->where($where);

        $result = $connection->fetchAll($select, []);
        if (count($result)) {
            foreach ($result as $row) {
                foreach ($orders as $key => $order) {
                    if ((string)$key === (string)$row['increment_id']) {
                        $existsOrders[$row['entity_id']] = $order;
                    }
                }
            }
        }
        return $existsOrders;
    }

    public function addQuote($item)
    {
        return '`'.$item.'`';
    }
}
