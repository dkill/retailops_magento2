<?php
namespace Gudtech\RetailOps\Controller\Adminhtml;

/**
 * Abstract log controller class.
 *
 */
abstract class Log extends \Magento\Backend\App\Action
{
    protected $resultPageFactory;
    protected $resultForwardFactory;
    protected $resultRedirectFactory;
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Backend\Model\View\Result\ForwardFactory $resultForwardFactory
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->resultForwardFactory = $resultForwardFactory;
        $this->resultRedirectFactory = $context->getResultRedirectFactory();
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
            'Gudtech_RetailOps::inventory'
        )->_addBreadcrumb(
            __('RetailOps'),
            __('Inventory logs')
        );
        return $this;
    }
}
