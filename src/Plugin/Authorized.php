<?php

namespace Gudtech\RetailOps\Plugin;

use Gudtech\RetailOps\Model\Logger\Monolog;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\AuthenticationException;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Webapi\ErrorProcessor;

/**
 * Authorized plugin class.
 *
 */
class Authorized
{
    const INTEGRATION_KEY_VALUE = 'retailops/retailops/password';
    const INTEGRATION_KEY = 'integration_auth_token';

    /**
     * @var ResponseInterface
     */
    protected $response;

    /**
     * @var ErrorProcessor
     */
    protected $errorProcessor;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * Authorized constructor.
     * @param Context $context
     * @param ScopeConfig $scopeConfig
     */
    public function __construct(Context $context, ScopeConfig $scopeConfig)
    {
        $this->response = $context->getResponse();
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @param $subject
     * @param $proceed
     * @param RequestInterface $request
     * @return ResponseInterface
     */
    public function aroundDispatch($subject, $proceed, $request)
    {
        try {
            $requestKey = $request->getPost(self::INTEGRATION_KEY);
            $configKey = $this->scopeConfig->getValue(self::INTEGRATION_KEY_VALUE);
            if (!$requestKey || $configKey !== $requestKey) {
                throw new \Magento\Framework\Exception\AuthenticationException(
                    __('Incorrect authorisation, API key not valid.')
                );
            }
            return $proceed($request);
        } catch (\Exception $exception) {
            if ($exception instanceof AuthenticationException) {
                $this->response->setContent($exception->getMessage());
                $this->response->setStatusCode('401');
            } else {
                print $exception;
                $this->response->setContent(__('Error occur while do request'));
                $this->response->setStatusCode('500');
            }
            $logger = ObjectManager::getInstance()->get(Monolog::class);
            $logger->addCritical('Error in retailops:'.$exception->getMessage(), (array)$request->getPost());
            return $this->response;
        }
    }
}
