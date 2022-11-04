<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2022 Improntus (http://www.improntus.com/)
 * @package Improntus_Rebill
 */

namespace Improntus\Rebill\Model\Sales;

use Exception;
use Magento\Framework\DB\TransactionFactory;
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
     * @var TransactionFactory
     */
    protected $transactionFactory;

    /**
     * @var Order\Payment\Repository
     */
    protected $paymentRepository;

    /**
     * @param BuilderInterface $transactionBuilder
     * @param TransactionFactory $transactionFactory
     * @param Order\Payment\Repository $paymentRepository
     */
    public function __construct(
        BuilderInterface         $transactionBuilder,
        TransactionFactory       $transactionFactory,
        Order\Payment\Repository $paymentRepository
    ) {
        $this->paymentRepository = $paymentRepository;
        $this->transactionBuilder = $transactionBuilder;
        $this->transactionFactory = $transactionFactory;
    }

    /**
     * @param Order $order
     * @return Order\Invoice|null
     * @throws LocalizedException
     * @throws Exception
     */
    public function execute(Order &$order)
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
            $this->paymentRepository->save($payment);
            $invoice->getOrder()->setIsInProcess(true);
            $transactionSave = $this->transactionFactory->create()
                ->addObject($invoice)
                ->addObject($invoice->getOrder());
            $transactionSave->save();
            return $invoice;
        }
        return null;
    }
}
