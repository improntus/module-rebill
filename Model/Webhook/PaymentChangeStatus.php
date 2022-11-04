<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2022 Improntus (http://www.improntus.com/)
 * @package Improntus_Rebill
 */

namespace Improntus\Rebill\Model\Webhook;

use Exception;
use Improntus\Rebill\Helper\Config;
use Improntus\Rebill\Model\Entity\Payment\Repository as PaymentRepository;
use Improntus\Rebill\Model\Sales\Invoice;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\CreditmemoFactory;
use Magento\Sales\Model\OrderRepository;
use Magento\Sales\Model\Service\CreditmemoService;

class PaymentChangeStatus extends WebhookAbstract
{
    private const MAPS_STATUS = [
        'SUCCEEDED' => 'processing',
        'PENDING' => 'pending',
        'REFUNDED' => 'closed',
        'FAILED' => 'canceled',
    ];

    /**
     * @var OrderRepository
     */
    private $orderRepository;

    /**
     * @var PaymentRepository
     */
    private $paymentRepository;

    /**
     * @var Invoice
     */
    private $invoiceProcessor;

    /**
     * @var CreditmemoFactory
     */
    private $creditMemoFactory;

    /**
     * @var CreditmemoService
     */
    private $creditMemoService;

    /**
     * @param OrderRepository $orderRepository
     * @param PaymentRepository $paymentRepository
     * @param Config $configHelper
     * @param Invoice $invoiceProcessor
     * @param CreditmemoFactory $creditMemoFactory
     * @param CreditmemoService $creditMemoService
     * @param array $parameters
     */
    public function __construct(
        OrderRepository   $orderRepository,
        PaymentRepository $paymentRepository,
        Invoice           $invoiceProcessor,
        CreditmemoFactory $creditMemoFactory,
        CreditmemoService $creditMemoService,
        array             $parameters = []
    )
    {
        $this->creditMemoFactory = $creditMemoFactory;
        $this->creditMemoService = $creditMemoService;
        $this->invoiceProcessor = $invoiceProcessor;
        $this->orderRepository = $orderRepository;
        $this->paymentRepository = $paymentRepository;
        parent::__construct($parameters);
    }

    /**
     * @return mixed|void
     * @throws AlreadyExistsException
     * @throws CouldNotSaveException
     * @throws InputException
     * @throws LocalizedException
     * @throws NoSuchEntityException
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
        $paymentModel->setStatus($newStatus);
        $this->paymentRepository->save($paymentModel);
        /** @var Order $orderModel */
        $orderModel = $this->orderRepository->get($paymentModel->getOrderId());
        if ($newStatus == 'SUCCEEDED') {
            if ($orderModel->canInvoice()) {
                $paymentList = $this->paymentRepository->getEzList(['order_id' => $paymentModel->getOrderId()]);
                $doInvoice = true;
                foreach ($paymentList->getItems() as $payment) {
                    if ($payment->getStatus() != 'SUCCEEDED' && $doInvoice) {
                        $doInvoice = false;
                    }
                }
                if ($doInvoice) {
                    $this->invoiceProcessor->execute($orderModel);
                }
            }
        }
        if ($newStatus == 'FAILED') {
            if ($orderModel->canCancel()) {
                $paymentList = $this->paymentRepository->getEzList(['order_id' => $paymentModel->getOrderId()]);
                $doCancel = true;
                foreach ($paymentList->getItems() as $payment) {
                    if ($payment->getStatus() != 'FAILED' && $doCancel) {
                        $doCancel = false;
                    }
                }
                if ($doCancel) {
                    $orderModel->cancel();
                } else {
                    $orderModel->setState('payment_review');
                    $orderModel->setStatus('payment_review');
                }
                $this->orderRepository->save($orderModel);
            }
        }
        if ($newStatus == 'REFUNDED') {
            $paymentList = $this->paymentRepository->getEzList(['order_id' => $paymentModel->getOrderId()]);
            $doRefunds = true;
            foreach ($paymentList->getItems() as $payment) {
                if ($payment->getStatus() != 'REFUNDED' && $doRefunds) {
                    $doRefunds = false;
                }
            }
            if ($doRefunds) {
                $creditMemo = $this->creditMemoFactory->createByOrder($orderModel);
                $this->creditMemoService->refund($creditMemo);
                $this->orderRepository->save($orderModel);
            }
        }
    }
}
