<?php

namespace Improntus\Rebill\Model\Webhook;

use Magento\Sales\Model\OrderRepository;
use Improntus\Rebill\Model\Entity\Payment\Repository as PaymentRepository;

class PaymentChangeStatus extends WebhookAbstract
{
    const MAPS_STATUS = [
        'SUCCEEDED' => 'complete',
        'PENDING' => 'pending',
        'REFUNDED' => 'closed',
        'CANCELLED' => 'canceled'
    ];

    /**
     * @param OrderRepository $orderRepository
     * @param PaymentRepository $paymentRepository
     * @param array $parameters
     */
    public function __construct(
        private OrderRepository   $orderRepository,
        private PaymentRepository $paymentRepository,
        array                     $parameters = []
    )
    {
        parent::__construct($parameters);
    }

    /**
     * @return mixed|void
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute()
    {
        $paymentResponse = $this->getParameter('payment');

        if (empty($paymentResponse)) {
            return;
        }
        if (empty($paymentResponse["id"]) || empty($paymentResponse["newStatus"])) {
            return;
        }

        $newStatus = $paymentResponse["newStatus"];
        if (!array_key_exists(strtoupper($newStatus), self::MAPS_STATUS)) {
            return;
        }

        $paymentModel = $this->paymentRepository->getByRebillId($paymentResponse["id"]);
        if (!$paymentModel) {
            return;
        }

        try {
            $paymentModel->setStatus($newStatus);
            $this->paymentRepository->save($paymentModel);

            $orderModel = $this->orderRepository->get($paymentModel->getOrderId());
            $orderModel->setStatus(self::MAPS_STATUS[$newStatus]);
            $this->orderRepository->save($orderModel);
        } catch (Exception $exception) {
            $this->configHelper->logError($exception->getMessage());
        }
    }
}
