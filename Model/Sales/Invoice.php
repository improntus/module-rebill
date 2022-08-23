<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2022 Improntus (http://www.improntus.com/)
 * @package Improntus_Rebill
 */

namespace Improntus\Rebill\Model\Sales;

use Magento\Sales\Model\Order;
use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface;

class Invoice
{
    /**
     * @var BuilderInterface
     */
    protected $transactionBuilder;

    /**
     * @param BuilderInterface $transactionBuilder
     */
    public function __construct(
        BuilderInterface $transactionBuilder
    ) {
        $this->transactionBuilder = $transactionBuilder;
    }

    /**
     * @param Order $order
     * @return Order\Invoice|null
     * @throws LocalizedException
     */
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
