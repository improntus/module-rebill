<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2022 Improntus (http://www.improntus.com/)
 * @package Improntus_Rebill
 */

namespace Improntus\Rebill\Block\Customer;

use Exception;
use Improntus\Rebill\Helper\Config;
use Improntus\Rebill\Model\Rebill\Card;
use Improntus\Rebill\Model\Rebill\Subscription;
use Magento\Customer\Model\Session;
use Magento\Framework\View\Element\Template;
use Improntus\Rebill\Model\Config\Source\CustomerDocumentType;

class Edit extends Template
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
     * @var \Improntus\Rebill\Helper\Subscription
     */
    protected $subscriptionHelper;

    /**
     * @var CustomerDocumentType
     */
    protected $customerDocumentType;

    /**
     * @param Template\Context $context
     * @param Session $session
     * @param Subscription $subscription
     * @param \Improntus\Rebill\Helper\Subscription $subscriptionHelper
     * @param Card $card
     * @param Config $configHelper
     * @param CustomerDocumentType $customerDocumentType
     * @param array $data
     */
    public function __construct(
        Template\Context                      $context,
        Session                               $session,
        Subscription                          $subscription,
        \Improntus\Rebill\Helper\Subscription $subscriptionHelper,
        Card                                  $card,
        Config                                $configHelper,
        CustomerDocumentType                  $customerDocumentType,
        array                                 $data = []
    ) {
        $this->customerDocumentType = $customerDocumentType;
        $this->subscriptionHelper = $subscriptionHelper;
        $this->card = $card;
        $this->configHelper = $configHelper;
        $this->subscription = $subscription;
        $this->session = $session;
        parent::__construct($context, $data);
    }

    public function getDocumentTypes($gateway)
    {
        return $this->customerDocumentType->toOptionArray($gateway);
    }

    /**
     * @return mixed|null
     * @throws Exception
     */
    public function getCustomerToken()
    {
        $customerEmail = $this->session->getCustomer()->getEmail();
        return $this->subscription->getCustomerToken($customerEmail)['token'];
    }

    /**
     * @return string
     */
    public function getApiUuid()
    {
        return $this->configHelper->getApiUuid();
    }

    /**
     * @return string
     */
    public function getSdkUrl()
    {
        $integratorMode = $this->configHelper->getIntegrationMode();
        return $integratorMode == 'sandbox' ? 'https://api.rebill.dev/v2' : 'https://api.rebill.to/v2';
    }

    /**
     * @return array|mixed|null
     * @description return array of active subscriptions
     */
    public function getSubscription()
    {
        try {
            $customerEmail = $this->session->getCustomer()->getEmail();
            $id = $this->getRequest()->getParam('id');
            return $this->subscription->getSubscription($id, $customerEmail);
        } catch (Exception $exception) {
            $this->configHelper->logError($exception->getMessage());
            return [];
        }
    }

    public function getSubscriptionText($subscription)
    {
        $details = [
            'frequency'         => $subscription['price']['frequency']['quantity'],
            'frequencyType'     => $subscription['price']['frequency']['type'],
            'recurringPayments' => $subscription['price']['repetitions'],
            'initialCost'       => 0,
        ];
        $price = $subscription['price']['amount'];
        return $this->subscriptionHelper->getFrequencyDescription(null, $details, $price);
    }

    /**
     * @param $card
     * @return string
     * @description return the credit card information
     */
    public function getPaymentMethod($card)
    {
        $cardNumber = chunk_split($card['bin'] . "******" . $card['last4'], 4, ' ');
        return $cardNumber . ' ' . $this->getCardDate($card);
    }

    /**
     * @param $cardId
     * @return mixed|null
     */
    public function getCard($cardId)
    {
        $customerEmail = $this->session->getCustomer()->getEmail();
        return $this->card->getCard($cardId, $customerEmail);
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
