<?php

namespace Gudtech\RetailOps\Controller\Order;

use Magento\Framework\App\ObjectManager;
use Gudtech\RetailOps\Controller\RetailOps;

/**
 * Order returned controller class
 *
 */
class Returned extends RetailOps
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
        $this->logger = $logger;
        parent::__construct($context);
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
