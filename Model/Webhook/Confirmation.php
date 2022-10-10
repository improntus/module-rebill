<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2022 Improntus (http://www.improntus.com/)
 * @package Improntus_Rebill
 */

namespace Improntus\Rebill\Model\Webhook;

use Improntus\Rebill\Helper\Config;
use Improntus\Rebill\Model\Entity\Price\Repository as PriceRepository;
use Improntus\Rebill\Model\Entity\Subscription\Repository as SubscriptionRepository;
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
     * @param Config $configHelper
     * @param Invoice $rebillInvoice
     * @param OrderRepository $orderRepository
     * @param SubscriptionRepository $subscriptionRepository
     * @param PriceRepository $priceRepository
     * @param OrderSender $orderSender
     * @param array $parameters
     */
    public function __construct(
        Config                 $configHelper,
        Invoice                $rebillInvoice,
        OrderRepository        $orderRepository,
        SubscriptionRepository $subscriptionRepository,
        PriceRepository        $priceRepository,
        OrderSender            $orderSender,
        array                  $parameters = []
    ) {
        $this->priceRepository = $priceRepository;
        $this->configHelper = $configHelper;
        $this->rebillInvoice = $rebillInvoice;
        $this->orderRepository = $orderRepository;
        $this->subscriptionRepository = $subscriptionRepository;
        $this->orderSender = $orderSender;
        parent::__construct($parameters);
    }

    /**
     * @return void
     */
    public function execute()
    {
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
                $subscriptions = [];
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
                        $subscriptions[] = [
                            'subscription_id' => $subscription['id'],
                            'price_id'        => $subscription['price']['id'],
                            'quantity'        => $subscription['quantity'],
                            'order_id'        => $orderId,
                            'status'          => 'updated',
                        ];
                    }
                }
                $prices = $this->priceRepository->getEzList([
                    'rebill_price_id' => ['in' => array_map(function ($subscription) {
                        return $subscription['price_id'];
                    }, $subscriptions)]
                ]);
                foreach ($subscriptions as $subscription) {
                    /** @var \Improntus\Rebill\Model\Subscription $sub */
                    $sub = $this->subscriptionFactory->create();
                    $sub->setData($subscription);
                    $sub->save();
                }
            }
        }
    }
}
