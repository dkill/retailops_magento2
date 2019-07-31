<?php

namespace Gudtech\RetailOps\Controller\Order;

use Gudtech\RetailOps\Model\Logger\Monolog;
use Gudtech\RetailOps\Model\Order\OrderReturn;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Gudtech\RetailOps\Controller\AbstractController;

/**
 * Return order controller class action.
 *
 */
class ReturnOrder extends AbstractController
{
    const ENABLE = 'retailops/retailops_feed/order_return';

    /**
     * @var OrderReturn
     */
    protected $orderReturn;

    protected $events;

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
        } catch (\Exception $e) {
            $event = [
                'event_type' => 'error',
                'code' => $e->getCode(),
                'message' => $e->getMessage(),
                'diagnostic_data' => 'string',
                'associations' => $this->association,
            ];
            $this->error = $e;
            $this->events[] = $event;
            $this->statusRetOps = 'error';

        } finally {
            if (!array_key_exists('events', $this->responseEvents)) {
                $this->responseEvents['events'] = [];
            }
//            $this->response['status'] = $this->response['status'] ?? $this->statusRetOps;
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
