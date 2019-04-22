<?php

namespace RetailOps\Api\Controller\Adminhtml\Queue;

class Cancel extends \RetailOps\Api\Controller\Adminhtml\Queue
{
    public function execute()
    {
        $this->_view->loadLayout(false);
        $this->_view->renderLayout();
    }
}
