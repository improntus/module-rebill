<?php

namespace Improntus\Rebill\Model\Sales;

use Magento\Sales\Model\Order;
use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface;

class Invoice
{
    protected $transactionBuilder;

    public function __construct(
        BuilderInterface $transactionBuilder
    ) {
        $this->transactionBuilder = $transactionBuilder;
    }

    public function execute(Order $order)
    {
        if ($order->canInvoice()) {
            $payment = $order->getPayment();
            $transaction = $this->transactionBuilder
                ->setPayment($payment)
                ->setOrder($order)
                ->setTransactionId($payment->getTransactionId())
                ->build(TransactionInterface::TYPE_AUTH);
            $payment->addTransactionCommentsToOrder($transaction, __('Invoice generation process'));
            $invoice = $payment->getOrder()->prepareInvoice();
            $invoice->register();
            if ($payment->getMethodInstance()->canCapture()) {
                $invoice->capture();
            }
            $payment->getOrder()->addRelatedObject($invoice);
            return $invoice;
        }
        return null;
    }
}
