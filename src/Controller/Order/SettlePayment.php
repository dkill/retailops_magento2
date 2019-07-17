<?php

namespace Gudtech\RetailOps\Controller\Order;

use Magento\Framework\App\ObjectManager;
use Gudtech\RetailOps\Controller\RetailOps;

/**
 * Order settle payment controller class
 *
 */
class SettlePayment extends RetailOps
{
    const SERVICENAME = 'catalog';
    /**
     * @var string
     */
    protected $areaName = self::BEFOREPULL.self::SERVICENAME;

    private $events = [];

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Gudtech\RetailOps\Model\Logger\Monolog $logger
    ) {
        parent::__construct($context);

        $this->logger = $logger;
    }

    public function execute()
    {
        $this->response['events'] = [];
        foreach ($this->events as $event) {
            $this->response['events'][] = $event;
        }
        $this->getResponse()->representJson(json_encode($this->response));
        $this->getResponse()->setStatusCode('200');
        parent::execute();
        return $this->getResponse();
    }
}
