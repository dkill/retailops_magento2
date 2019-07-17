<?php

namespace Gudtech\RetailOps\Controller\Order;

use Gudtech\RetailOps\Controller\RetailOps;
use Gudtech\RetailOps\Model\Logger\Monolog;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;

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

    /**
     * Returned constructor.
     *
     * @param Context $context
     * @param Monolog $logger
     * @param ScopeConfigInterface $config
     */
    public function __construct(
        Context $context,
        Monolog $logger,
        ScopeConfigInterface $config
    ) {
        $this->logger = $logger;
        parent::__construct($context, $config);
    }

    public function execute()
    {
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
