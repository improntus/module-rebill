<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2022 Improntus (http://www.improntus.com/)
 * @package Improntus_Rebill
 */

namespace Improntus\Rebill\Block\Customer;

use Exception;
use Improntus\Rebill\Helper\Config;
use Improntus\Rebill\Model\Rebill\Subscription;
use Magento\Customer\Model\Session;
use Magento\Framework\View\Element\Template;

class Subscriptions extends Template
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
     * @var Config
     */
    protected $configHelper;

    /**
     * @param Template\Context $context
     * @param Session $session
     * @param Subscription $subscription
     * @param Config $configHelper
     * @param array $data
     */
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

    /**
     * @return array|mixed|null
     * @description return array of active subscriptions
     */
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

    /**
     * @param $subscription
     * @return string
     * @description return the credit card information
     */
    public function getPaymentMethod($subscription)
    {
        $card = $subscription['invoices'][0]['buyer']['card'];
        return "**** **** **** " . $card['last4'] . ' ' . $this->getCardDate($card);
    }

    /**
     * @param $card
     * @return string
     * @description return the credit card expiration date
     */
    protected function getCardDate($card)
    {
        $month = $card['expiration']['month'];
        $year = $card['expiration']['year'];

        $month = $month > 9 ? $month : '0' . $month;
        $year = substr($year, -2);
        return "$month/$year";
    }
}
