<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2022 Improntus (http://www.improntus.com/)
 * @package Improntus_Rebill
 */

namespace Improntus\Rebill\Model\Webhook;

use Exception;
use Improntus\Rebill\Helper\Config;
use Improntus\Rebill\Model\Entity\Price\Model;
use Improntus\Rebill\Model\Entity\Price\Repository as PriceRepository;
use Improntus\Rebill\Model\Entity\Subscription\Repository as SubscriptionRepository;
use Improntus\Rebill\Model\Entity\SubscriptionShipment\Repository as ShipmentRepository;
use Improntus\Rebill\Model\Entity\Payment\Repository as PaymentRepository;
use Improntus\Rebill\Model\Payment\Transaction;
use Improntus\Rebill\Model\Sales\Invoice;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Sales\Model\OrderRepository;

class Confirmation extends WebhookAbstract
{
    /**
     * @var Invoice
     */
    protected $rebillInvoice;

    /**
     * @var OrderRepository
     */
    protected $orderRepository;

    /**
     * @var SubscriptionRepository
     */
    protected $subscriptionRepository;

    /**
     * @var OrderSender
     */
    protected $orderSender;

    /**
     * @var Config
     */
    protected $configHelper;

    /**
     * @var PriceRepository
     */
    protected $priceRepository;

    /**
     * @var ShipmentRepository
     */
    protected $shipmentRepository;

    /**
     * @var PaymentRepository
     */
    protected $paymentRepository;

    /**
     * @param Config $configHelper
     * @param Invoice $rebillInvoice
     * @param OrderRepository $orderRepository
     * @param SubscriptionRepository $subscriptionRepository
     * @param ShipmentRepository $shipmentRepository
     * @param PriceRepository $priceRepository
     * @param PaymentRepository $paymentRepository
     * @param OrderSender $orderSender
     * @param array $parameters
     */
    public function __construct(
        Config                 $configHelper,
        Invoice                $rebillInvoice,
        OrderRepository        $orderRepository,
        SubscriptionRepository $subscriptionRepository,
        ShipmentRepository     $shipmentRepository,
        PriceRepository        $priceRepository,
        PaymentRepository      $paymentRepository,
        OrderSender            $orderSender,
        array                  $parameters = []
    ) {
        $this->paymentRepository = $paymentRepository;
        $this->priceRepository = $priceRepository;
        $this->configHelper = $configHelper;
        $this->rebillInvoice = $rebillInvoice;
        $this->orderRepository = $orderRepository;
        $this->subscriptionRepository = $subscriptionRepository;
        $this->shipmentRepository = $shipmentRepository;
        $this->orderSender = $orderSender;
        parent::__construct($parameters);
    }

    /**
     * @return void
     */
    public function execute()
    {
        try {
            $invoiceId = $this->getParameter('invoice_id');
            if ($invoiceId) {
                $invoice = $this->subscriptionRepository->getInvoiceById($invoiceId);
                if (isset($invoice['id'])) {
                    $orderId = $this->getParameter('order_id');
                    /** @var Order $order */
                    $order = $this->orderRepository->get($orderId);
                    $this->rebillInvoice->execute($order);
                    $order->setStatus($this->configHelper->getApprovedStatus());
                    $this->orderSender->send($order);
                    $order->setIsCustomerNotified(true);
                    $this->orderRepository->save($order);
                    foreach ($invoice['paidBags'] as $_payment) {
                        $payment = $this->paymentRepository->getByRebillId($_payment['payment']['id']);
                        $payment->setRebillId($_payment['payment']['id']);
                        $payment->setStatus($_payment['payment']['status']);
                        $this->paymentRepository->save($payment);
                    }
                    $subscriptions = [];
                    $prices = [];
                    $this->getSubscriptions($invoice, $subscriptions, $prices);
                    foreach ($subscriptions as $hash => $_subscriptions) {
                        if ($hash == Transaction::getDefaultFrequencyHash()) {
                            continue;
                        }
                        $shipment = $_subscriptions['shipment'][0];
                        /** @var Model $price */
                        $price = $prices[$shipment['price']['id']];
                        $shipmentModel = $this->shipmentRepository->getByRebillId($shipment['id']);
                        $shipmentModel->setStatus($shipment['status']);
                        $shipmentModel->setRebillId($shipment['id']);
                        $shipmentModel->setRebillPriceId($price->getRebillPriceId());
                        $shipmentModel->setOrderId($orderId);
                        $shipmentModel->setQuantity(1);
                        $shipmentModel->setDetails($shipment);
                        $shipmentModel->setPayed(1);
                        $this->shipmentRepository->save($shipmentModel);
                        foreach ($_subscriptions['product'] as $subscription) {
                            /** @var Model $price */
                            $price = $prices[$subscription['price']['id']];
                            $model = $this->subscriptionRepository->getByRebillId($subscription['id']);
                            $model->setShipmentId($shipmentModel->getId());
                            $model->setStatus($subscription['status']);
                            $model->setRebillId($subscription['id']);
                            $model->setRebillPriceId($price->getRebillPriceId());
                            $model->setOrderId($orderId);
                            $model->setQuantity(1);
                            $model->setDetails($subscription);
                            $packageHash = hash('md5', "$orderId-{$price->getFrequencyHash()}");
                            $model->setPackageHash($packageHash);
                            $model->setPayed(1);
                            $this->subscriptionRepository->save($model);
                        }
                    }
                }
            }
        } catch (Exception $exception) {
            $this->configHelper->logError(json_encode($this->getParameters()));
            $this->configHelper->logError($exception->getMessage());
        }
    }

    /**
     * @param array $invoice
     * @param array $subscriptions
     * @param array $prices
     * @return void
     * @throws Exception
     */
    private function getSubscriptions(array $invoice, array &$subscriptions, array &$prices)
    {
        foreach ($invoice['paidBags'] as $paidBag) {
            foreach ($paidBag['schedules'] as $schedule) {
                $subscription = $this->subscriptionRepository->getRebillSubscription(
                    $schedule,
                    $invoice['buyer']['customer']['userEmail']
                );
                if (!isset($subscription['id'])
                    || ($subscription['remainingIterations'] <= 0
                        && strtotime($subscription['nextChargeDate']) < time())) {
                    continue;
                }
                $priceId = $subscription['price']['id'];
                $prices[$priceId] = $this->priceRepository->getByRebillId($subscription['price']['id']);
                $type = $prices[$priceId]->getType();
                $hash = $prices[$priceId]->getFrequencyHash();
                $subscriptions[$hash][$type][] = $subscription;
            }
        }
    }
}
