<?php

namespace Gudtech\RetailOps\Controller;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Exception\NotFoundException;

/**
 * Abstract RetailOps controller class.
 *
 */
abstract class RetailOps extends Action
{
    const BEFOREPULL = 'retailops_before_pull_';

    /**
     * @var string
     */
    protected $action;

    /**
     * @var  \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var int
     */
    protected $version;

    /**
     * @var int
     */
    protected $client_id;

    /**
     * @var int
     */
    protected $channel_info;

    /**
     * @var \Exception
     */
    protected $error;

    /**
     * @var array
     */
    protected $_response;

    /**
     * @var int
     */
    protected $status = 200;

    /**
     * @var array
     */
    protected $association = [];

    /**
     * RetailOps constructor.
     *
     * @param Context $context
     * @param ScopeConfigInterface $config
     */
    public function __construct(
        Context $context,
        ScopeConfigInterface $config
    ) {
        $this->config = $config;
        parent::__construct($context);
    }

    /**
     * @param RequestInterface $request
     * @return ResponseInterface
     * @throws NotFoundException
     */
    public function dispatch(RequestInterface $request)
    {
        $this->setParams($request);
        return parent::dispatch($request);
    }

    /**
     * Use this method for logging system
     */
    public function execute()
    {
        $this->_eventManager->dispatch($this->areaName, [
            'response' => $this->_response,
            'request' => $this->getRequest(),
            'error' => $this->error,
            'status' => $this->status,
        ]);
    }

    /**
     * @param RequestInterface $request
     */
    protected function setParams($request)
    {
        $this->setAction($request);
        $this->setVersion($request);
        $this->setClientId($request);
        $this->setChannelInfo($request);
    }

    /**
     * @param RequestInterface $request
     */
    protected function setAction($request)
    {
        $this->action = $request->getParam('action');
    }

    /**
     * @param RequestInterface $request
     */
    protected function setVersion($request)
    {
        $this->version = $request->getParam('version');
    }

    /**
     * @param RequestInterface $request
     */
    protected function setClientId($request)
    {
        $this->client_id = $request->getParam('client_id');
    }

    /**
     * @param RequestInterface $request
     */
    protected function setChannelInfo($request)
    {
        $this->channel_info = $request->getParam('channel_info');
    }

    /**
     * @return string
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * @return int
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * @return int
     */
    public function getClientId()
    {
        return $this->client_id;
    }

    /**
     * @return int
     */
    public function getChannelInfo()
    {
        return $this->channel_info;
    }
}
