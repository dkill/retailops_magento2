<?php

namespace Gudtech\RetailOps\Model\Api\Traits;

use Gudtech\RetailOps\Model\Logger\Monolog;
use LogicException;
use Magento\Framework\Api\Filter as FilterApi;
use Magento\Framework\Api\Search\FilterGroup;
use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\ResourceConnection;
use Magento\Sales\Api\OrderRepositoryInterface;

trait Filter
{
    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var Monolog
     */
    protected $logger;

    /**
     * @var SearchCriteria
     */
    protected $searchCriteria;

    /**
     * @var FilterApi
     */
    protected $filter;

    /**
     * @var FilterGroup
     */
    protected $filterGroup;

    /**
     * @param array $filters
     * @return mixed
     */
    public function createFilterGroups(array $filters)
    {
        $filterGroup = $this->filterGroup->create();
        $filterGroup->setFilters($filters);
        return $filterGroup;
    }

    /**
     * @param $field
     * @param $operator
     * @param $value
     * @return FilterApi
     */
    public function createFilter($field, $operator, $value)
    {
        $filter = $this->filter->create();
        $filter->setField($field)
            ->setConditionType($operator)
            ->setValue($value);
        return $filter;
    }

    /**
     * @param string|int $orderIncrementId
     * @return string|null
     */
    public function getOrderIdByOrderIncrementId($orderIncrementId)
    {
        $orders[$orderIncrementId] = 1;
        $ordersId = array_keys($this->setOrderIdByOrderIncrementId($orders));
        if (!is_array($ordersId) || !count($ordersId)) {
            throw new LogicException(__('This increment id doesn\'t exists'));
        }
        return reset($ordersId);
    }

    /**
     * @param array $orders
     * @return array
     */
    public function setOrderIdByOrderIncrementId($orders = [])
    {
        $existsOrders = [];

        if (count($orders)) {
            $resource = ObjectManager::getInstance()->get(ResourceConnection::class);
            $connection = $resource->getConnection();
            $template = 'increment_id IN (%s)';
            $orderKeys = array_keys($orders);
            array_walk($orderKeys, [$this, 'addQuote']);
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
        }
        return $existsOrders;
    }

    /**
     * @param string $item
     * @return string
     */
    public function addQuote($item)
    {
        return "'" . $item . "'";
    }
}
