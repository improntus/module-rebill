<?php

namespace Improntus\Rebill\Block\Customer;

use Exception;
use Improntus\Rebill\Helper\Config;
use Improntus\Rebill\Model\Rebill\Subscription;
use Magento\Customer\Model\Session;
use Magento\Framework\View\Element\Template;

class Subscriptions extends Template
{
    protected $session;
    protected $subscription;
    protected $configHelper;

    public function __construct(
        Template\Context $context,
        Session          $session,
        Subscription     $subscription,
        Config           $configHelper,
        array            $data = []
    ) {
        $this->configHelper = $configHelper;
        $this->subscription = $subscription;
        $this->session = $session;
        parent::__construct($context, $data);
    }

    public function getSubscriptions()
    {
        try {
            $customerEmail = $this->session->getCustomer()->getEmail();
            $subscriptions = $this->subscription->getSubscriptionFromClient($customerEmail);
            foreach ($subscriptions as $index => $subscription) {
                if ($subscription['status'] != 'ACTIVE') {
                    unset($subscriptions[$index]);
                }
            }
            return $subscriptions;
        } catch (Exception $exception) {
            $this->configHelper->logError($exception->getMessage());
            return [];
        }
    }

    public function getPaymentMethod($subscription)
    {
        $card = $subscription['invoices'][0]['buyer']['card'];
        return $card['last4'] . ' ' . $this->getCardDate($card);
    }

    protected function getCardDate($card)
    {
        $month = $card['expiration']['month'];
        $year = $card['expiration']['year'];

        $month = $month > 9 ? $month : '0' . $month;
        $year = substr($year, -2);
        return "$month/$year";
    }
}
