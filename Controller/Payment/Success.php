<?php

namespace Improntus\Rebill\Controller\Payment;

use Magento\Sales\Model\Order;
use Improntus\Rebill\Helper\Config;
use Magento\Sales\Model\OrderRepository;
use Improntus\Rebill\Model\Sales\Invoice;
use Improntus\Rebill\Model\Rebill\Subscription;
use Improntus\Rebill\Model\SubscriptionFactory;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;

class Success extends Action
{
    protected $session;
    protected $subscription;
    protected $subscriptionFactory;

    /**
     * @var OrderRepository
     */
    protected $orderRepository;
    protected $invoice;
    protected $configHelper;

    public function __construct(
        Context             $context,
        Session             $session,
        Subscription        $subscription,
        SubscriptionFactory $subscriptionFactory,
        OrderRepository     $orderRepository,
        Invoice             $invoice,
        Config              $configHelper
    ) {
        $this->configHelper = $configHelper;
        $this->invoice = $invoice;
        $this->orderRepository = $orderRepository;
        $this->session = $session;
        $this->subscription = $subscription;
        $this->subscriptionFactory = $subscriptionFactory;
        parent::__construct($context);
    }

    public function execute()
    {
        $invoice = $this->getRequest()->getParam('invoice');
        if (isset($invoice['id'])) {
            $rebillInvoice = $this->subscription->getInvoice($invoice['id']);
            if (isset($rebillInvoice['id'])) {
                $orderId = $this->getRequest()->getParam('order_id');
                /** @var Order $order */
                $order = $this->orderRepository->get($orderId);
                $magentoInvoice = $this->invoice->execute($order);
                $order->setStatus($this->configHelper->getApprovedStatus())->save();
                $subscriptions = $this->subscription->getSubscriptionFromClient($this->session->getCustomer()->getEmail());
                $_subscriptions = [];
                foreach ($subscriptions as $subscription) {
                    if ($invoice['id'] == $subscription['invoices'][0]['id']) {
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
    }
}
