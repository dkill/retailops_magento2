<?php

namespace Gudtech\RetailOps\Controller;

use Magento\Framework\App\ActionFactory;
use Magento\Framework\App\ResponseInterface;

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
        'catalog_get_config_v1' => \Gudtech\RetailOps\Controller\Catalog\GetConfig::class,
        'catalog_push_v1' => \Gudtech\RetailOps\Controller\Catalog\Push::class,
        'inventory_push_v1' => \Gudtech\RetailOps\Controller\Inventory\Push::class,
        'order_acknowledge_v1' => \Gudtech\RetailOps\Controller\Order\Acknowledge::class,
        'order_cancel_v1' => \Gudtech\RetailOps\Controller\Order\Cancel::class,
        'order_complete_v1' => \Gudtech\RetailOps\Controller\Order\Complete::class,
        'order_pull_v1' => \Gudtech\RetailOps\Controller\Order\Pull::class,
        'order_returned_v1' => \Gudtech\RetailOps\Controller\Order\Returned::class,
        'order_settle_payment_v1' => \Gudtech\RetailOps\Controller\Order\SettlePayment::class,
        'order_shipment_submit_v1' => \Gudtech\RetailOps\Controller\Order\Shipment::class,
        'order_update_v1' => \Gudtech\RetailOps\Controller\Order\Update::class
    ];

    /**
     * @var ActionFactory
     */
    protected $actionFactory;

    /**
     * Response
     *
     * @var ResponseInterface
     */
    protected $response;

    /**
     * @param ActionFactory $actionFactory
     * @param ResponseInterface $response
     */
    public function __construct(
        ActionFactory $actionFactory,
        ResponseInterface $response
    ) {
        $this->actionFactory = $actionFactory;
        $this->response = $response;
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
            $parameters = new \Zend\Stdlib\Parameters(json_decode($request->getContent(), true));
            //fix error with empty content
            $request->setPost($parameters);
            $request->setModuleName($path[0]);
            return $this->actionFactory->create(
                self::$map[$path[1]],
                ['request' => $request]
            );
        }
        return null;
    }
}
