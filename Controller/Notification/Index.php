<?php

namespace Improntus\Rebill\Controller\Notification;

use Improntus\Rebill\Model\Subscription;
use Improntus\Rebill\Model\SubscriptionFactory;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;

class Index extends Action implements HttpGetActionInterface
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
                'status'          => 'outdated',
                'order_id'        => null
            ]);
            $subscription->save();
        }
        /**
         * @todo 24hs heads up
         */
        if ($id = $this->getRequest()->getParam('id')) {
            /** @var Subscription $subscription */
            $subscription = $this->subscriptionFactory->create();
            $subscription->load($id, 'subscription_id');
            $subscription->setData([
                'subscription_id' => $id,
                'price_id'        => $this->getRequest()->getParam('price')['id'],
                'quantity'        => $this->getRequest()->getParam('quantity'),
                'status'          => 'outdated',
                'order_id'        => null
            ]);
            $subscription->save();
        }
    }
}
