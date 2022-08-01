<?php

namespace Improntus\Rebill\Controller\Payment;

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

    public function __construct(
        Context $context,
        Session $session,
        Subscription $subscription,
        SubscriptionFactory $subscriptionFactory
    ) {
        $this->session = $session;
        $this->subscription = $subscription;
        $this->subscriptionFactory = $subscriptionFactory;
        parent::__construct($context);
    }

    public function execute()
    {
        $invoice = $this->getRequest()->getParam('invoice');
        if ($invoice) {
            $orderId = $this->getRequest()->getParam('order_id');
            $subscriptions = $this->subscription->getSubscriptionFromClient($this->session->getCustomer()->getEmail());
            $_subscriptions = [];
            foreach ($subscriptions as $subscription) {
                if ($invoice['id'] == $subscription['invoices'][0]['id']) {
                    $_subscriptions[] = [
                        'subscription_id' => $subscription['id'],
                        'price_id'        => $subscription['price']['id'],
                        'quantity'        => $subscription['quantity'],
                        'order_id'        => $orderId,
                        'status'          => 'updated'
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
