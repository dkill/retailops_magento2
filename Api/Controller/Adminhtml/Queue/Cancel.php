<?php

namespace RetailOps\Api\Controller\Adminhtml\Queue;

/**
 * Queue cancel controller class action.
 *
 */
class Cancel extends \RetailOps\Api\Controller\Adminhtml\Queue
{
    public function execute()
    {
        $this->_view->loadLayout(false);
        $this->_view->renderLayout();
    }
}
