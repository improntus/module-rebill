<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2022 Improntus (http://www.improntus.com/)
 * @package Improntus_Rebill
 */

namespace Improntus\Rebill\Controller\Payment;

use Magento\Sales\Model\Order;
use Improntus\Rebill\Helper\Config;
use Magento\Sales\Model\OrderRepository;
use Improntus\Rebill\Model\Sales\Invoice;
use Magento\Framework\App\ResponseInterface;
use Improntus\Rebill\Model\Rebill\Subscription;
use Improntus\Rebill\Model\SubscriptionFactory;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Framework\Exception\AlreadyExistsException;

class Success extends Action
{
    /**
     * @var Session
     */
    protected $session;

    /**
     * @var Subscription
     */
    protected $subscription;

    /**
     * @var SubscriptionFactory
     */
    protected $subscriptionFactory;

    /**
     * @var OrderRepository
     */
    protected $orderRepository;

    /**
     * @var Invoice
     */
    protected $invoice;

    /**
     * @var Config
     */
    protected $configHelper;

    /**
     * @var OrderSender
     */
    protected $orderSender;

    /**
     * @param Context $context
     * @param Session $session
     * @param Subscription $subscription
     * @param SubscriptionFactory $subscriptionFactory
     * @param OrderRepository $orderRepository
     * @param Invoice $invoice
     * @param Config $configHelper
     * @param OrderSender $orderSender
     */
    public function __construct(
        Context             $context,
        Session             $session,
        Subscription        $subscription,
        SubscriptionFactory $subscriptionFactory,
        OrderRepository     $orderRepository,
        Invoice             $invoice,
        Config              $configHelper,
        OrderSender         $orderSender
    ) {
        $this->configHelper = $configHelper;
        $this->invoice = $invoice;
        $this->orderRepository = $orderRepository;
        $this->session = $session;
        $this->subscription = $subscription;
        $this->subscriptionFactory = $subscriptionFactory;
        $this->orderSender = $orderSender;

        parent::__construct($context);
    }

    /**
     * @return void
     * @throws AlreadyExistsException
     * @throws InputException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function execute()
    {
        $invoiceId = $this->getRequest()->getParam('invoice_id');
        if ($invoiceId) {
            $invoice = $this->subscription->getInvoice($invoiceId);
            if (isset($invoice['id'])) {
                $orderId = $this->getRequest()->getParam('order_id');
                /** @var Order $order */
                $order = $this->orderRepository->get($orderId);
                $this->invoice->execute($order);
                $order->setStatus($this->configHelper->getApprovedStatus());
                $this->orderSender->send($order);
                $order->setIsCustomerNotified(true);
                $this->orderRepository->save($order);
                $_subscriptions = [];
                foreach ($invoice['paidBags'] as $paidBag) {
                    foreach ($paidBag['schedules'] as $schedule) {
                        $subscription = $this->subscription->getSubscription($schedule, $invoice['buyer']['customer']['userEmail']);
                        if (!isset($subscription['id'])
                            || ($subscription['remainingIterations'] <= 0 && strtotime($subscription['nextChargeDate']) < time())) {
                            continue;
                        }
                        $_subscriptions[] = [
                            'subscription_id' => $subscription['id'],
                            'price_id'        => $subscription['price']['id'],
                            'quantity'        => $subscription['quantity'],
                            'order_id'        => $orderId,
                            'status'          => 'updated',
                        ];
                    }
                }
                foreach ($_subscriptions as $subscription) {
                    /** @var \Improntus\Rebill\Model\Subscription $sub */
                    $sub = $this->subscriptionFactory->create();
                    $sub->setData($subscription);
                    $sub->save();
                }
            }
        }
        $this->_redirect('checkout/onepage/success');
    }
}
