<?php

namespace Improntus\Rebill\Controller\Notification;

use Improntus\Rebill\Model\Subscription;
use Improntus\Rebill\Model\SubscriptionFactory;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;

class NewPayment extends Action implements HttpGetActionInterface
{
    protected $subscriptionFactory;

    public function __construct(
        Context             $context,
        SubscriptionFactory $subscriptionFactory
    ) {
        $this->subscriptionFactory = $subscriptionFactory;
        parent::__construct($context);
    }

    public function execute()
    {
        /**
         * @todo new payment or payment status changed
         */
        if ($request = $this->getRequest()->getParam('payment')) {

        }
        /**
         * @todo new subscription
         */
        if ($request = $this->getRequest()->getParam('subscription')) {
            /** @var Subscription $subscription */
            $subscription = $this->subscriptionFactory->create();
            $subscription->load($request['id'], 'subscription_id');
            $subscription->setData([
                'subscription_id' => $request['id'],
                'price_id'        => $request['price']['id'],
                'quantity'        => $request['quantity'],
                'status'          => 'new_subscription_generated',
                'order_id'        => null
            ]);
            $subscription->save();
        }
    }
}
