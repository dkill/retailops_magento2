<?php
$objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();
$orderService = \Magento\TestFramework\ObjectManager::getInstance()->create(
    \Magento\Sales\Api\InvoiceManagementInterface::class
);
/** @var \Magento\Sales\Model\Order $order */
$order = $objectManager->get(\Magento\Sales\Model\Order::class);
$order = $order->loadByIncrementId('100000001');
$invoice = $orderService->prepareInvoice($order);
$invoice->register();
$order = $invoice->getOrder();
$order->setIsInProcess(true);
$transactionSave = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()
    ->create(\Magento\Framework\DB\Transaction::class);
$transactionSave->addObject($invoice)->addObject($order)->save();
