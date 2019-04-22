<?php

namespace RetailOps\Api\Controller\Adminhtml\Log;

/**
 * Log index controller action class.
 *
 */
class Index extends \RetailOps\Api\Controller\Adminhtml\Log
{
    public function execute()
    {
        if ($this->getRequest()->getQuery('ajax')) {
            $resultForward = $this->resultForwardFactory->create();
            $resultForward->forward('grid');
            return $resultForward;
        }

        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('RetailOps_Api::inventory');
        $resultPage->getConfig()->getTitle()->prepend(__('Logs'));
        $resultPage->addBreadcrumb(__('RetailOps'), __('RetailOps'));
        $resultPage->addBreadcrumb(__('Inventory logs'), __('Inventory logs'));
        return $resultPage;
    }
}