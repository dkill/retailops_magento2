<?php

namespace Gudtech\RetailOps\Controller\Adminhtml\Queue;

/**
 * Queue mass delete controller class action.
 *
 */
class MassDelete extends \Gudtech\RetailOps\Controller\Adminhtml\Queue
{
    public function execute()
    {
        $collection = $this->_objectManager->create(\Gudtech\RetailOps\Model\ResourceModel\Queue\Collection::class);
        $collection = $this->massFilter->getCollection($collection);
        $collectionSize = $collection->getSize();

        foreach ($collection as $item) {
            $item->delete();
        }

        $this->messageManager->addSuccess(__('A total of %1 record(s) have been deleted.', $collectionSize));

        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();

        return $resultRedirect->setPath('*/*/');
    }
}
