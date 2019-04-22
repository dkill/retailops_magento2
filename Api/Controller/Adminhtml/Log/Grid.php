<?php

namespace RetailOps\Api\Controller\Adminhtml\Log;

/**
 * Log grid controller action class.
 *
 */
class Grid extends \RetailOps\Api\Controller\Adminhtml\Log
{
    public function execute()
    {
        $this->_view->loadLayout(false);
        $this->_view->renderLayout();
    }
}
