<?php

namespace Gudtech\RetailOps\Model\Pull;

use Magento\Framework\Api\FilterFactory;
use Magento\Framework\Api\Search\FilterGroupFactory;
use Magento\Framework\Exception\AuthenticationException;
use Magento\Framework\ObjectManagerInterface;
use Gudtech\RetailOps\Model\Api\Map\Order as OrderMap;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order as MagentoOrder;

/**
 * Order pull class.
 *
 */
class Order
{
    use \Gudtech\RetailOps\Model\Api\Traits\Filter;

    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var int
     */
    protected $currentPage;

    /**
     * @var \Magento\Framework\Api\SearchCriteria
     */
    protected $searchCriteria;

    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var int|null
     */
    protected $countPages=1;

    /**
     * @var Magento\Framework\Api\Filter[]
     */
    private $filters=[];

    /**
     * @var FilterFactory
     */
    protected $filter;

    /**
     * @var FilterGroupFactory
     */
    protected $filterGroup;

    /**
     * @var OrderMap
     */
    private $retailOrderMaps;

    public function __construct(
        OrderRepositoryInterface $orderRepository,
        ObjectManagerInterface $objectManager,
        OrderMap $retailOrderMaps,
        FilterFactory $filter,
        FilterGroupFactory $filterGroupFactory
    ) {
        $this->orderRepository = $orderRepository;
        $this->objectManager = $objectManager;
        $this->retailOrderMaps = $retailOrderMaps;
        $this->searchCriteria = $this->objectManager->create(\Magento\Framework\Api\SearchCriteria::class);
        $this->filter = $filter;
        $this->filterGroup = $filterGroupFactory;
    }

    public function getOrders($pageToken, $maxcount = 1, $data = [])
    {
        $this->setFilters($pageToken, $maxcount, $data);
        // Create the order repo and get a list of orders matching our criteria
        $result = $this->orderRepository->getList($this->searchCriteria);
        $this->countPages = $result->getLastPageNumber();
        $orderItems = $this->retailOrderMaps->getOrders($result->getItems());
        if ($this->getNextPageToken()) {
            $orders['next_page_token'] = $this->getNextPageToken();
        }
        $orders['orders'] = $orderItems;
        return $orders;
    }

    protected function getNextPageToken()
    {
        if ($this->countPages > $this->currentPage) {
            $service = $this->objectManager->get(\Gudtech\RetailOps\Service\NumberPageToken::class);
            $pageNumberToken = $service->encode($this->currentPage+1);
            return $pageNumberToken;
        }
        return null;
    }

    protected function setFilters($pageToken, $maxcount, $data)
    {
        $this->setData($pageToken, $maxcount, $data);
        $this->addOrderStatuses();
        $this->addInvoiceExlude();
        $this->setSpecificOrders($data);
        $this->addFilterGroups();
    }

    private function setData($pageToken, $maxcount, $data)
    {
            $filter = $this->createFilter(
                'retailops_send_status',
                'in',
                [OrderMap::ORDER_STATUS_PENDING, OrderMap::ORDER_STATUS_PULLED]
            );
            $this->addFilter('retail_status', $filter);
            $page = $this->getCurrentPage($pageToken);
            $this->searchCriteria->setPageSize($maxcount);
            $this->searchCriteria->setCurrentPage($page);
            $this->currentPage = $page;
    }

    private function addOrderStatuses()
    {
        $this->addExludeStatuses();
        $this->addIncludeStatuses();
    }

    private function addExludeStatuses()
    {
        $filter = $this->createFilter('state', 'nin', [MagentoOrder::STATE_CANCELED, MagentoOrder::STATE_HOLDED]);
        $this->addFilter('order_not_send_status', $filter);
    }

    private function addIncludeStatuses()
    {
        $filter = $this->createFilter('status', 'in', [MagentoOrder::STATE_PROCESSING]);
        $this->addFilter('order_send_status', $filter);
    }

    private function addInvoiceExlude()
    {
        $filter = $this->createFilter('base_total_invoiced', 'gt', 0);
        $this->addFilter('order_should_invoiced', $filter);
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
        $groups = [];

        if (($filters = $this->getFilters()) && count($filters)) {
            foreach ($filters as $key => $filter) {
                $groups[] = $filter;
            }
        }
            $this->searchCriteria->setFilterGroups($groups)
                ->setSortOrders($this->createSortOrder('created_at', 'asc'));
    }

    private function setSpecificOrders($data)
    {
        if (isset($data['specific_orders'])) {
            $orders_id =  $this->getIdOrders($data['specific_orders']);
            $this->resetFilters();
            $filter = $this->createFilter('entity_id', 'in', array_keys($orders_id));
            $this->addFilter('specificOrder', $filter);
        }
    }

    private function resetFilters()
    {
        $this->filters = [];
    }

    private function getIdOrders($orders)
    {
        $orders_id = [];
        if (count($orders)) {
            foreach ($orders as $order_id) {
                $val = $order_id['channel_refnum'];
                if (is_numeric($val)) {
                    $orders_id[$val] = 1;
                }
            }
        }
        return $this->setOrderIdByOrderIncrementId($orders_id);
    }

    protected function getCurrentPage($pageToken = null)
    {
        $page = 1;
        if ($pageToken) {
            if ($pageToken === 'string') {
                return $page;
            }
            $service = $this->objectManager->get(\Gudtech\RetailOps\Service\NumberPageToken::class);
            $pageNumber = $service->decode($pageToken);
            if (is_numeric($pageNumber)) {
                $page = (int)$pageNumber;
            } else {
                $logger = $this->objectManager->get(\Psr\Log\LoggerInterface::class);
                $logger->addCritical($pageToken. ' is invalid');
                throw new \Magento\Framework\Exception\AuthenticationException(__('Page token are invalid'));
            }
        }
        return $page;
    }

    /**
     * create a sort order with the given field and direction
     *
     * @param  \string $field
     * @param  \string $direction
     * @return \Magento\Framework\Api\SortOrder
     */
    private function createSortOrder($field, $direction)
    {
        // Create a sort order
        $sortOrder = $this->objectManager->create(\Magento\Framework\Api\SortOrder::class);
        $sortOrder->setField($field)
            ->setDirection($direction);

        return [$sortOrder];
    }
}
