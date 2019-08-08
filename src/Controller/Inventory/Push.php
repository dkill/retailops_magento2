<?php

namespace Gudtech\RetailOps\Controller\Inventory;

use Gudtech\RetailOps\Model\Logger\Monolog;
use Gudtech\RetailOps\Model\Inventory\Push as InventoryPush;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Area;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ObjectManager;
use Gudtech\RetailOps\Controller\AbstractController;

/**
 * Inventory push controller class
 *
 */
class Push extends AbstractController
{
    /**
     * @var string
     */
    const PARAM = 'inventory_updates';

    /**
     * @var string
     */
    const SERVICENAME = 'inventory';

    /**
     * @var string
     */
    const ENABLE = 'retailops/retailops_feed/inventory_push';

    /**
     * @var string
     */
    protected $areaName = self::BEFOREPULL . self::SERVICENAME;

    /**
     * @var array
     */
    protected $events = [];

    /**
     * @var array
     */
    protected $responseEvents = [];

    /**
     * @var string
     */
    protected $statusRetOps = 'success';

    /**
     * @var Inventory
     */
    protected $inventory;

    /**
     * @var array
     */
    protected $association = [];

    /**
     * Push constructor.
     *
     * @param Context $context
     * @param CalculateInventory $inventory
     * @param Monolog $logger
     * @param ScopeConfigInterface $config
     */
    public function __construct(
        Context $context,
        InventoryPush $inventory,
        Monolog $logger,
        ScopeConfigInterface $config
    ) {
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

            $inventoryUpdates = $this->getRequest()->getParam(self::PARAM);

            foreach($inventoryUpdates as $inventory) {
                $this->logger->addInfo("Processing inventory for SKU: ". $inventory['sku']);
                $this->inventory->processData($inventory);
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
