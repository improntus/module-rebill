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
 * @description Webhook for 24 hours heads up before new payment in a recurrent or cycling subscription
 */
class HeadsUp extends Action implements HttpGetActionInterface
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
                'order_id'        => null,
            ]);
            $subscription->save();
        }
    }
}
