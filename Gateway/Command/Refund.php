<?php

namespace Improntus\Rebill\Gateway\Command;

use Exception;
use Improntus\Rebill\Model\Entity\Payment\Repository as PaymentRepository;
use Improntus\Rebill\Model\Rebill\Payment as RebillPayment;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Payment\Gateway\Command\ResultInterface;
use Magento\Payment\Gateway\CommandInterface;

class Refund implements CommandInterface
{
    /**
     * @var RebillPayment $rebillPayment
     */
    protected $rebillPayment;

    /**
     * @var PaymentRepository $paymentRepository
     */
    protected $paymentRepository;

    /**
     * @param RebillPayment $rebillPayment
     * @param PaymentRepository $paymentRepository
     */
    public function __construct(
        RebillPayment     $rebillPayment,
        PaymentRepository $paymentRepository
    ) {
        $this->rebillPayment = $rebillPayment;
        $this->paymentRepository = $paymentRepository;
    }

    /**
     * @param array $commandSubject
     * @return ResultInterface|void|null
     * @throws CouldNotSaveException
     */
    public function execute(array $commandSubject)
    {
        /**
         * La estructura del dato de entrada la conozco a partir de
         * \Magento\Payment\Model\Method\Adapter::refund
         */
        $commandArgPayment = $commandSubject['payment'];
        $order = $commandArgPayment->getOrder();

        $items = $this->paymentRepository->getEzList([
            'order_id' => ['eq' => $order->getId()],
            'status'   => ['eq' => 'SUCCEEDED'],
        ])->getItems();

        $throwException = false;
        foreach ($items as $payment) {
            $response = $this->rebillPayment->refundPaymentById(
                $payment->getData('rebill_id')
            );

            if (isset($response['statusCode']) || (!isset($response['status']))) {
                /** Si es OK, la respuesta no trae 'statusCode', y si trae 'status' (el nuevo estado del pago: "REFUNDED"). */
                $throwException = true;
                continue;
            }

            /** Luego de hacer el reembolso actualizo el pago en Magento */
            $payment->setStatus($response['status']);
            $this->paymentRepository->save($payment);
        }

        if ($throwException) {
            /** La razón de arrojar una excepción es que no se termine de realizar el reembolos en Magento. */
            throw new Exception(__('Unable to refund payment on Rebill.'));
        }
    }
}
