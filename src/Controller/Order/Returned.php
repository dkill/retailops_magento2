<?php

namespace Gudtech\RetailOps\Controller\Order;

use Gudtech\RetailOps\Model\Logger\Monolog;
use Gudtech\RetailOps\Model\Order\Returned as OrderReturn;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Gudtech\RetailOps\Controller\AbstractController;

/**
 * Order returned controller class
 *
 */
class Returned extends AbstractController
{
    /**
     * @var string
     */
    const SERVICENAME = 'order_return';

    /**
     * @var string
     */
    const ENABLE = 'retailops/retailops_feed/order_return';

    /**
     * @var string
     */
    protected $statusRetOps = 'success';

    /**
     * @var string
     */
    protected $areaName = self::BEFOREPULL . self::SERVICENAME;

    /**
     * @var OrderReturn
     */
    protected $orderReturn;

    /**
     * @var array
     */
    protected $events = [];

    /**
     * @var array
     */
    protected $responseEvents = [];

    /**
     * ReturnOrder constructor.
     *
     * @param Context $context
     * @param OrderReturn $orderReturn
     * @param Monolog $logger
     * @param ScopeConfigInterface $config
     */
    public function __construct(
        Context $context,
        OrderReturn $orderReturn,
        Monolog $logger,
        ScopeConfigInterface $config
    ) {
        $this->orderReturn = $orderReturn;
        $this->logger = $logger;
        parent::__construct($context, $config);
    }

    public function execute()
    {
        try {
            if (!$this->config->getValue(self::ENABLE)) {
                throw new \LogicException('API endpoint has been disabled');
            }
            $postData = (array)$this->getRequest()->getPost();
            $response = $this->orderReturn->returnOrder($postData);
            $this->responseEvents = $response;
        } catch (\Exception $exception) {
            $event = [
                'event_type' => 'error',
                'code' => $exception->getCode(),
                'message' => $exception->getMessage(),
                'diagnostic_data' => 'string',
                'associations' => $this->association,
            ];
            $this->error = $exception;
            $this->events[] = $event;
            $this->statusRetOps = 'error';
            parent::execute();
        } finally {
            $this->responseEvents['events'] = $this->responseEvents['events'] ?? [];
            $this->responseEvents['status'] = $this->statusRetOps;
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
