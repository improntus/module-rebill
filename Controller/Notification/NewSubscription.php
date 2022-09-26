<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2022 Improntus (http://www.improntus.com/)
 * @package Improntus_Rebill
 */

namespace Improntus\Rebill\Controller\Notification;

use Exception;
use Improntus\Rebill\Model\Subscription;
use Magento\Framework\App\ResponseInterface;
use Improntus\Rebill\Model\SubscriptionFactory;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\App\Action\HttpGetActionInterface;

/**
 * @description Webhook for new subscription
 */
class NewSubscription extends Action implements HttpGetActionInterface
{
    /**
     * @var SubscriptionFactory
     */
    protected $subscriptionFactory;

    /**
     * @param Context $context
     * @param SubscriptionFactory $subscriptionFactory
     */
    public function __construct(
        Context             $context,
        SubscriptionFactory $subscriptionFactory
    ) {
        $this->subscriptionFactory = $subscriptionFactory;
        parent::__construct($context);
    }

    /**
     * @return ResponseInterface|ResultInterface|void
     * @throws Exception
     */
    public function execute()
    {
        /**
         * @todo new subscription
         */
        if ($request = $this->getRequest()->getParam('subscription')) {
            try {
                /** @var Subscription $subscription */
                $subscription = $this->subscriptionFactory->create();
                $subscription->load($request['id'], 'subscription_id');
                $subscription->setData([
                    'subscription_id' => $request['id'],
                    'price_id'        => $request['price']['id'],
                    'quantity'        => $request['quantity'],
                    'status'          => 'new_subscription_generated',
                    'order_id'        => null,
                ]);
                $subscription->save();
            } catch (Exception $exception) {

            }
        }
    }
}
