<?php

namespace Retailops\Api\Plugin;

/**
 * Csrf validator skip class.
 */
class CsrfValidatorSkip
{
    /**
     * Module frontname
     *
     * @var string
     */
    const MODULE_FRONTNAME = 'retailops';

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
        if ($request->getModuleName() == self::MODULE_FRONTNAME) {
            return;
        }

        $proceed($request, $action);
    }
}