<?php

namespace RetailOps\Api\Controller\Catalog;

use RetailOps\Api\Controller\RetailOps;

/**
 * Catalog push controller class
 *
 */
class GetConfig extends RetailOps
{
    const SERVICENAME = 'catalog';

    /**
     * @var string
     */
    protected $areaName = self::BEFOREPULL . self::SERVICENAME;

    private $events = [];

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \RetailOps\Api\Logger\Logger $logger
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
