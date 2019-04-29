<?php

namespace RetailOps\Api\Plugin;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\AuthenticationException;

/**
 * Authorized plugin class.
 *
 */
class Authorized
{
    const INTEGRATION_KEY_VALUE = 'retailops/retailops/password';
    const INTEGRATION_KEY = 'integration_auth_token';

    /**
     * @var \Magento\Framework\App\ResponseInterface
     */
    protected $response;

    /**
     * @var \Magento\Framework\Webapi\ErrorProcessor
     */
    protected $errorProcessor;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    public function aroundDispatch($subject, $proceed, $request)
    {
        try {
            $key = $request->getPost(self::INTEGRATION_KEY);
            $valid_key = $this->scopeConfig->getValue(self::INTEGRATION_KEY_VALUE);
            if (!$key || $valid_key !== $key) {
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
            $logger = ObjectManager::getInstance()->get(\RetailOps\Api\Model\Logger\Monolog::class);
            $logger->addCritical('Error in retailops:'.$exception->getMessage(), (array)$request->getPost());
            return $this->response;
        }
    }

    public function __construct(Context $context, \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig)
    {
        $this->response = $context->getResponse();
        $this->scopeConfig = $scopeConfig;
    }
}
