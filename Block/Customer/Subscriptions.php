<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2022 Improntus (http://www.improntus.com/)
 * @package Improntus_Rebill
 */

namespace Improntus\Rebill\Block\Customer;

use Exception;
use Improntus\Rebill\Helper\Config;
use Magento\Customer\Model\Session;
use Improntus\Rebill\Model\Rebill\Card;
use Magento\Framework\View\Element\Template;
use Improntus\Rebill\Model\Rebill\Subscription;

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
     * @var Card
     */
    protected $card;

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
        Card             $card,
        array            $data = []
    ) {
        $this->configHelper = $configHelper;
        $this->subscription = $subscription;
        $this->session = $session;
        $this->card = $card;
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
        $customerEmail = $this->session->getCustomer()->getEmail();
        $card = $this->card->getCard($subscription["card"], $customerEmail);
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
