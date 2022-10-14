<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2022 Improntus (http://www.improntus.com/)
 * @package Improntus_Rebill
 */

namespace Improntus\Rebill\Block\Customer;

use Exception;
use Improntus\Rebill\Helper\Config;
use Improntus\Rebill\Model\Entity\Subscription\Repository;
use Improntus\Rebill\Model\ResourceModel\Subscription\Collection;
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
     * @var Repository
     */
    protected $subscriptionRepository;

    /**
     * @param Template\Context $context
     * @param Session $session
     * @param Subscription $subscription
     * @param Repository $subscriptionRepository
     * @param Config $configHelper
     * @param Card $card
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        Session          $session,
        Subscription     $subscription,
        Repository       $subscriptionRepository,
        Config           $configHelper,
        Card             $card,
        array            $data = []
    ) {
        $this->subscriptionRepository = $subscriptionRepository;
        $this->configHelper = $configHelper;
        $this->subscription = $subscription;
        $this->session = $session;
        $this->card = $card;
        parent::__construct($context, $data);
    }

    /**
     * @return Collection|null
     * @description return array of active subscriptions
     */
    public function getSubscriptions()
    {
        try {
            $subscriptions = $this->subscriptionRepository->getCollection();
            $subscriptions->getSelect()->joinInner(
                ['so' => 'sales_order'],
                'so.entity_id = main_table.order_id',
                []
            );
            $subscriptions->addFieldToFilter('so.customer_id', $this->session->getCustomerId());
            return $subscriptions;
//            $customerEmail = $this->session->getCustomer()->getEmail();
//            $subscriptions = $this->subscription->getSubscriptionFromClient($customerEmail);
//            foreach ($subscriptions as $index => $subscription) {
//                if ($subscription['status'] != 'ACTIVE') {
//                    unset($subscriptions[$index]);
//                }
//            }
//            return $subscriptions;
        } catch (Exception $exception) {
            $this->configHelper->logError($exception->getMessage());
        }
        return null;
    }

    /**
     * @param array $subscription
     * @return string
     * @description return the credit card information
     */
    public function getPaymentMethod(array $subscription)
    {
        $customerEmail = $this->session->getCustomer()->getEmail();
        $card = $this->card->getCard($subscription["card"], $customerEmail);
        return "**** **** **** " . $card['last4'] . ' ' . $this->getCardDate($card);
    }

    /**
     * @param array $card
     * @return string
     * @description return the credit card expiration date
     */
    protected function getCardDate(array $card)
    {
        $month = $card['expiration']['month'];
        $year = $card['expiration']['year'];

        $month = $month > 9 ? $month : '0' . $month;
        $year = substr($year, -2);
        return "$month/$year";
    }
}
