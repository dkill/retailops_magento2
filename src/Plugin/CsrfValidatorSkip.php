<?php

namespace Gudtech\RetailOps\Plugin;

use Gudtech\RetailOps\Controller\Router;

/**
 * Csrf validator skip class.
 */
class CsrfValidatorSkip
{

    /**
     * @param \Magento\Framework\App\Request\CsrfValidator $subject
     * @param \Closure $proceed
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Magento\Framework\App\ActionInterface $action
     */
    public function aroundValidate(
        $subject,
        \Closure $proceed,
        $request,
        $action
    ) {
        if ($request->getModuleName() == Router::MODULE_FRONTNAME) {
            return;
        }

        $proceed($request, $action);
    }
}