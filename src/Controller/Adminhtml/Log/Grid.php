<?php

namespace Gudtech\RetailOps\Controller\Adminhtml\Log;

/**
 * Log grid controller action class.
 *
 */
class Grid extends \Gudtech\RetailOps\Controller\Adminhtml\Log
{
    public function execute()
    {
        $this->_view->loadLayout(false);
        $this->_view->renderLayout();
    }
}
