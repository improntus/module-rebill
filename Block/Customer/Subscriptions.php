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
use Zend_Db_Expr;

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
            $subscriptions->getSelect()->joinLeft(
                ['rss' => 'rebill_subscription_shipment'],
                'rss.entity_id = main_table.shipment_id',
                []
            );
            //phpcs:disable
            $subscriptions->getSelect()->reset('columns');
            $subscriptions->getSelect()->columns([
                '*',
                'title'    => new Zend_Db_Expr("GROUP_CONCAT(JSON_UNQUOTE(JSON_EXTRACT(main_table.details, '$.title')) SEPARATOR ', ')"),
                'shipment' => new Zend_Db_Expr("JSON_UNQUOTE(JSON_EXTRACT(rss.details, '$.title'))"),
                'price'    => new Zend_Db_Expr("SUM(JSON_UNQUOTE(JSON_EXTRACT(main_table.details, '$.price.amount')) + JSON_UNQUOTE(JSON_EXTRACT(rss.details, '$.price.amount')))"),
            ]);
            //phpcs:enable
            $subscriptions->addFieldToFilter('so.customer_id', $this->session->getCustomerId());
            $subscriptions->getSelect()->group('package_hash');
            return $subscriptions;
        } catch (Exception $exception) {
            $this->configHelper->logError($exception->getMessage());
        }
        return null;
    }

    /**
     * @param array $subscription
     * @param array $cards
     * @return string
     */
    public function getPaymentMethod(array $subscription, array $cards)
    {
        foreach ($cards as $card) {
            if ($card['id'] == $subscription['card']) {
                return "**** **** **** " . $card['last4'] . ' ' . $this->getCardDate($card);
            }
        }
        return '';
    }

    /**
     * @param string $id
     * @return mixed|null
     */
    public function getCustomerCards(string $id)
    {
        return $this->card->getCards($id);
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
