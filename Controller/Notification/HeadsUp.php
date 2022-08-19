<?php

namespace Improntus\Rebill\Controller\Notification;

use Improntus\Rebill\Model\Subscription;
use Improntus\Rebill\Model\SubscriptionFactory;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;

class HeadsUp extends Action implements HttpGetActionInterface
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
                'status'          => 'recalculate',
                'order_id'        => null
            ]);
            $subscription->save();
        }
    }
}
