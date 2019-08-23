<?php

namespace Gudtech\RetailOps\Service;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Service\InvoiceService;
use Magento\Sales\Model\Order\Invoice;

/**
 * Invoice helper class.
 *
 */
class InvoiceHelper
{
    public static $captureOnlinePayment = [
        'braintree'=>1,
        'paypal'=>1
    ];
    /**
     * @var InvoiceService
     */
    private $invoiceService;

    /**
     * InvoiceHelper constructor.
     *
     * @param InvoiceService $invoiceService
     */
    public function __construct(InvoiceService $invoiceService)
    {
        $this->invoiceService = $invoiceService;
    }

    /**
     * @param Order $order
     * @param $items
     * @return bool
     * @throws LocalizedException
     */
    public function createInvoice(Order $order, $items = [])
    {
        if (!count($items)>0) {
            return false;
        }
        if ($order->canInvoice()) {
            $invoice = $this->invoiceService->prepareInvoice($order, $items);
            if ($this->captureOnline($order)) {
                $invoice->setRequestedCaptureCase(\Magento\Sales\Model\Order\Invoice::CAPTURE_ONLINE);
            }
            if (!$invoice) {
                throw new LocalizedException(__("We can't save the invoice right now."));
            }

            $invoice->addComment('Invoiced by RetailOps');
            $invoice->register();
            $invoice->getOrder()->setIsInProcess(true);

            return $this->saveInvoice($invoice);
        } else {
            return false;
        }

        return $invoice->getId() ? true : false;
    }

    public function captureOnline(Order $order)
    {
        $method = $order->getPayment()->getMethod();
        if (array_key_exists($method, $this::$captureOnlinePayment)) {
            return true;
        }

        return false;
    }

    /**
     * @param Invoice $invoice
     */
    public function saveInvoice(Invoice $invoice)
    {
        $transactionSave = ObjectManager::getInstance()->create(
            \Magento\Framework\DB\Transaction::class
        )->addObject(
            $invoice
        )->addObject(
            $invoice->getOrder()
        )->save();
        return $invoice;
    }
}
