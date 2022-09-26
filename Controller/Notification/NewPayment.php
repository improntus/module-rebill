<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2022 Improntus (http://www.improntus.com/)
 * @package Improntus_Rebill
 */

namespace Improntus\Rebill\Controller\Notification;

use Exception;
use Improntus\Rebill\Model\Rebill\Subscription;
use Improntus\Rebill\Model\Rebill\Payment;
use Magento\Framework\App\ResponseInterface;
use Improntus\Rebill\Model\SubscriptionFactory;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\App\Action\HttpGetActionInterface;

/**
 * @description Webhook for new payment
 */
class NewPayment extends Action implements HttpGetActionInterface
{
    /**
     * @var SubscriptionFactory
     */
    protected $subscriptionFactory;

    /**
     * @var Subscription
     */
    protected $subscription;

    /**
     * @var Payment
     */
    protected $payment;

    /**
     * @param Context $context
     * @param Payment $payment
     * @param Subscription $subscription
     * @param SubscriptionFactory $subscriptionFactory
     */
    public function __construct(
        Context             $context,
        Payment             $payment,
        Subscription        $subscription,
        SubscriptionFactory $subscriptionFactory
    ) {
        $this->payment = $payment;
        $this->subscriptionFactory = $subscriptionFactory;
        $this->subscription = $subscription;
        parent::__construct($context);
    }

    /**
     * @return ResponseInterface|ResultInterface|void
     * @throws Exception
     */
    public function execute()
    {
        /**
         * @todo new payment or payment status changed
         */
        if ($request = $this->getRequest()->getParam('payment')) {
            try {
                $payment = $this->payment->getPaymentById($request['id']);
                if (!isset($payment['billingSchedulesId'])) {
                    return;
                }
                foreach ($payment['billingSchedulesId'] as $id) {
                    $schedule = $this->subscription->getSubscription($id, $payment['payer']['email']);
                    if (!isset($schedule['id']) || ($schedule['remainingIterations'] <= 0 && strtotime($schedule['nextChargeDate']) < time())) {
                        continue;
                    }
                    $subscription = $this->subscriptionFactory->create();
                    $subscription->load($schedule['id'], 'subscription_id');
                    $subscription->setData([
                        'subscription_id' => $schedule['id'],
                        'price_id'        => $schedule['price']['id'],
                        'quantity'        => $schedule['quantity'],
                        'status'          => 'new_payment',
                        'order_id'        => $subscription->getData('order_id'),
                    ]);
                    $subscription->save();
                }
            } catch (Exception $exception) {

            }
        }
    }
}
