<?php

namespace RetailOps\Api\Controller;

use Magento\Framework\App\ObjectManager;

/**
 * Router class for RetailOps API endpoints.
 *
 */
class Router implements \Magento\Framework\App\RouterInterface
{
    const MODULE_ENABLE = 'retailops/retailops/turn_on';

    /**
     * Module frontname
     *
     * @var string
     */
    const MODULE_FRONTNAME = 'retailops';

    /**
     *
     * @var array
     */
    protected static $map = [
        'catalog_get_config_v1' => \RetailOps\Api\Controller\Catalog\GetConfig::class,
        'catalog_push_v1' => \RetailOps\Api\Controller\Catalog\Push::class,
        'inventory_push_v1' => \RetailOps\Api\Controller\Inventory\Push::class,
        'order_acknowledge_v1' => \RetailOps\Api\Controller\Order\Acknowledge::class,
        'order_cancel_v1' => \RetailOps\Api\Controller\Order\Cancel::class,
        'order_complete_v1' => \RetailOps\Api\Controller\Order\Complete::class,
        'order_pull_v1' => \RetailOps\Api\Controller\Order\Pull::class,
        'order_returned_v1' => \RetailOps\Api\Controller\Order\Returned::class,
        'order_settle_payment_v1' => \RetailOps\Api\Controller\Order\SettlePayment::class,
        'order_shipment_submit_v1' => \RetailOps\Api\Controller\Order\Shipment::class,
        'order_update_v1' => \RetailOps\Api\Controller\Order\Update::class
    ];

    /**
     * @var \Magento\Framework\App\ActionFactory
     */
    protected $actionFactory;

    /**
     * Response
     *
     * @var \Magento\Framework\App\ResponseInterface
     */
    protected $_response;

    /**
     * @param \Magento\Framework\App\ActionFactory $actionFactory
     * @param \Magento\Framework\App\ResponseInterface $response
     */
    public function __construct(
        \Magento\Framework\App\ActionFactory $actionFactory,
        \Magento\Framework\App\ResponseInterface $response
    ) {
        $this->actionFactory = $actionFactory;
        $this->_response = $response;
    }

    /**
     * Validate and Match
     *
     * @param \Magento\Framework\App\RequestInterface $request
     * @return bool
     */
    public function match(\Magento\Framework\App\RequestInterface $request)
    {
        $scopeConfig = \Magento\Framework\App\ObjectManager::getInstance()->get(
            \Magento\Framework\App\Config\ScopeConfigInterface::class
        );

        if (!$scopeConfig->getValue(self::MODULE_ENABLE)) {
            return null;
        }

        if (!$request->isPost()) {
            return null;
        }

        $identifier = trim($request->getPathInfo(), '/');
        $path = explode('/', $identifier);

        if (count($path) !== 2) {
            return null;
        }

        if ($path[0] !== self::MODULE_FRONTNAME) {
            return null;
        }

        if (isset(self::$map[$path[1]])) {
            $content = file_get_contents('php://input');
            $paremeters = new \Zend\Stdlib\Parameters(json_decode($content, true));
            //fix error with empty content
            $request->setPost($paremeters);
            $request->setModuleName($path[0]);
            return $this->actionFactory->create(
                self::$map[$path[1]],
                ['request' => $request]
            );
        }
        return null;
    }
}
