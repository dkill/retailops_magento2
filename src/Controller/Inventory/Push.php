<?php

namespace Gudtech\RetailOps\Controller\Inventory;

use Gudtech\RetailOps\Model\Logger\Monolog;
use Gudtech\RetailOps\Model\RoRicsLinkUpcRepository;
use Gudtech\RetailOps\Service\CalculateInventory;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Area;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ObjectManager;
use Gudtech\RetailOps\Controller\RetailOps;

/**
 * Inventory push controller class
 *
 */
class Push extends RetailOps
{
    const PARAM = 'inventory_updates';
    const SKU = 'sku';
    const QUANTITY = 'calc_inventory';
    const SERVICENAME = 'inventory';
    const ENABLE = 'retailops/retailops_feed/inventory_push';
    /**
     * @var string
     */
    protected $areaName = self::BEFOREPULL . self::SERVICENAME;
    protected $events = [];
    protected $responseEvents = [];
    protected $statusRetOps = 'success';
    /**
     * @var CalculateInventory
     */
    protected $inventory;
    protected $association = [];
    /**
     * @var RoRicsLinkUpcRepository
     */
    protected $upcRepository;

    /**
     * Push constructor.
     *
     * @param Context $context
     * @param RoRicsLinkUpcRepository $linkUpcRepository
     * @param CalculateInventory $inventory
     * @param Monolog $logger
     * @param ScopeConfigInterface $config
     */
    public function __construct(
        Context $context,
        RoRicsLinkUpcRepository $linkUpcRepository,
        CalculateInventory $inventory,
        Monolog $logger,
        ScopeConfigInterface $config
    ) {
        $this->upcRepository = $linkUpcRepository;
        $this->inventory = $inventory;
        $this->logger = $logger;
        parent::__construct($context, $config);
    }

    public function execute()
    {
        try {
            if (!$this->config->getValue(self::ENABLE)) {
                throw new \LogicException('API endpoint has been disabled');
            }
            $inventories = $this->getRequest()->getParam(self::PARAM);
            $inventoryObjects = [];
            if (is_array($inventories) && count($inventories)) {
                $inventory = [];
                $inventories = $this->inventory->calculateInventory($inventories);
                foreach ($inventories as $invent) {
                    $upcs = $this->upcRepository->getProductUpcByRoUpc($invent[self::SKU]);
                    //if for one rics_integration Id can be many products
                    foreach ($upcs as $upc) {
                        $object = ObjectManager::getInstance()->create(\Gudtech\RetailOps\Model\Inventory::class);
                        $object->setUPC($upc);
                        $object->setCount($invent[self::QUANTITY]);
                        $inventoryObjects[] = $object;
                    }
                    $upcs = [];
                }
                $this->inventory->addInventoiesFromNotSendedOrderYet($inventoryObjects);
                $inventoryApi = ObjectManager::getInstance()->create(\Gudtech\RetailOps\Model\Inventory\Inventory::class);
                foreach ($inventoryObjects as $inventory) {
                    $this->association[] = ['identifier_type' => 'sku_number', 'identifier'=>$inventory->getUPC()];
                }
                $state = ObjectManager::getInstance()->get(\Magento\Framework\App\State::class);
                $state->emulateAreaCode(
                    Area::AREA_WEBAPI_REST,
                    [$inventoryApi, 'setInventory'],
                    [$inventoryObjects]
                );
            }
        } catch (\Exception $exception) {
            $event = [
                'event_type' => 'error',
                'code' => $exception->getCode(),
                'message' => $exception->getMessage(),
                'diagnostic_data' => 'string',
                'associations' => $this->association,
            ];
            $this->events[] = $event;
            $this->statusRetOps = 'error';

        } finally {
            $this->responseEvents['events'] = [];
            foreach ($this->events as $event) {
                $this->responseEvents['events'][] = $event;
            }
            $this->getResponse()->representJson(json_encode($this->responseEvents));
            $this->getResponse()->setStatusCode('200');
            parent::execute();
            return $this->getResponse();
        }
    }
}
