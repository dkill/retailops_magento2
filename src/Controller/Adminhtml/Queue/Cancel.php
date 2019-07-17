<?php

namespace Gudtech\RetailOps\Controller\Adminhtml\Queue;

/**
 * Queue cancel controller class action.
 *
 */
class Cancel extends \Gudtech\RetailOps\Controller\Adminhtml\Queue
{
    public function execute()
    {
        $this->_view->loadLayout(false);
        $this->_view->renderLayout();
    }
}
