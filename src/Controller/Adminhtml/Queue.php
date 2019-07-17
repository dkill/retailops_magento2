<?php

namespace Gudtech\RetailOps\Controller\Adminhtml;

/**
 * Abstract queue controller class.
 *
 */
abstract class Queue extends \Magento\Backend\App\Action
{
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var \Magento\Backend\Model\View\Result\ForwardFactory
     */
    protected $resultForwardFactory;
    /**
     * @var \Magento\Framework\Controller\Result\RedirectFactory
     */
    protected $resultRedirectFactory;
    /**
     * @var \Magento\Ui\Component\MassAction\Filter
     */
    protected $massFilter;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Backend\Model\View\Result\ForwardFactory $resultForwardFactory,
        \Magento\Ui\Component\MassAction\Filter $massFilter
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->resultForwardFactory = $resultForwardFactory;
        $this->resultRedirectFactory = $context->getResultRedirectFactory();
        $this->massFilter = $massFilter;
        parent::__construct($context);
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Gudtech_RetailOps::inventory');
    }

    protected function _init()
    {
        $this->_view->loadLayout();
        $this->_setActiveMenu(
            'Gudtech_RetailOps::cancel_queue'
        )->_addBreadcrumb(
            __('RetailOps'),
            __('Cancel queue')
        );
        return $this;
    }
}
