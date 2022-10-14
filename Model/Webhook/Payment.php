<?php

namespace Improntus\Rebill\Model\Webhook;

use Exception;
use Improntus\Rebill\Helper\Config;
use Improntus\Rebill\Model\Entity\Payment\Repository as PaymentRepository;
use Improntus\Rebill\Model\Entity\Price\Repository as PriceRepository;
use Improntus\Rebill\Model\Entity\Subscription\Model;
use Improntus\Rebill\Model\Entity\Subscription\Repository as SubscriptionRepository;
use Improntus\Rebill\Model\Entity\SubscriptionShipment\Repository as ShipmentRepository;
use Improntus\Rebill\Model\Rebill\Payment as RebillPayment;
use Improntus\Rebill\Model\Sales\Invoice;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderRepository;

class Payment extends WebhookAbstract
{
    /**
     * @var OrderRepository
     */
    protected $orderRepository;

    /**
     * @var SubscriptionRepository
     */
    protected $subscriptionRepository;

    /**
     * @var ShipmentRepository
     */
    protected $shipmentRepository;

    /**
     * @var PriceRepository
     */
    protected $priceRepository;

    /**
     * @var Invoice
     */
    protected $invoice;

    /**
     * @var RebillPayment
     */
    protected $rebillPayment;

    /**
     * @var PaymentRepository
     */
    protected $paymentRepository;

    /**
     * @var Config
     */
    protected $configHelper;

    /**
     * @param Config $configHelper
     * @param Invoice $invoice
     * @param OrderRepository $orderRepository
     * @param SubscriptionRepository $subscriptionRepository
     * @param ShipmentRepository $shipmentRepository
     * @param PriceRepository $priceRepository
     * @param PaymentRepository $paymentRepository
     * @param RebillPayment $rebillPayment
     * @param array $parameters
     */
    public function __construct(
        Config                 $configHelper,
        Invoice                $invoice,
        OrderRepository        $orderRepository,
        SubscriptionRepository $subscriptionRepository,
        ShipmentRepository     $shipmentRepository,
        PriceRepository        $priceRepository,
        PaymentRepository      $paymentRepository,
        RebillPayment          $rebillPayment,
        array                  $parameters = []
    ) {
        $this->configHelper = $configHelper;
        $this->paymentRepository = $paymentRepository;
        $this->rebillPayment = $rebillPayment;
        $this->invoice = $invoice;
        $this->orderRepository = $orderRepository;
        $this->subscriptionRepository = $subscriptionRepository;
        $this->shipmentRepository = $shipmentRepository;
        $this->priceRepository = $priceRepository;
        parent::__construct($parameters);
    }

    /**
     * @return mixed|void
     */
    public function execute()
    {
        $_payment = $this->getParameter('payment');
        try {
            if (isset($_payment['id'])) {
                $rebillPayment = $this->rebillPayment->getPaymentById($_payment['id']);
                if (!isset($rebillPayment['id']) && $rebillPayment['status'] !== 'SUCCEEDED') {
                    return;
                }
                $payment = $this->paymentRepository->getByRebillId($rebillPayment['id']);
                $payment->setRebillId($rebillPayment['id']);
                $payment->setStatus($rebillPayment['status']);
                $payment->setDetails($rebillPayment);
                $this->paymentRepository->save($payment);
                $packagesHashes = [];
                foreach ($rebillPayment['billingSchedulesId'] as $subscriptionId) {
                    $subscription = $this->subscriptionRepository->getByRebillId($subscriptionId);
                    if (!$subscription->getId()) {
                        $subscription = $this->shipmentRepository->getByRebillId($subscriptionId);
                    }
                    if (!$subscription->getId()) {
                        continue;
                    }
                    $subscription->setPayed(1);
                    if ($subscription instanceof Model) {
                        $packagesHashes[$subscription->getPackageHash()] = $subscription->getPackageHash();
                        $this->subscriptionRepository->save($subscription);
                    } else {
                        $this->shipmentRepository->save($subscription);
                    }
                }
                foreach ($packagesHashes as $hash) {
                    $packages = $this->subscriptionRepository->getCollection();
                    $packages->addFieldToFilter('package_hash', $hash);
                    $subscriptionsQty = $packages->getSize();
                    $payed = 0;
                    /** @var Model $_subscription */
                    foreach ($packages as $_subscription) {
                        $payed += $_subscription->getPayed();
                    }
                    if ($_subscription && $_subscription->getShipmentId()) {
                        $shipment = $this->shipmentRepository->getById($_subscription->getShipmentId());
                        $payed += $shipment->getPayed();
                    }
                    if ($payed == $subscriptionsQty && $_subscription) {
                        /** @var Order $order */
                        $order = $this->orderRepository->get($_subscription->getOrderId());
                        $this->invoice->execute($order);
                    }
                }
            }
        } catch (Exception $exception) {
            $this->configHelper->logError($exception->getMessage());
        }
    }
}
