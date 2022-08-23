<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2022 Improntus (http://www.improntus.com/)
 * @package Improntus_Rebill
 */

namespace Improntus\Rebill\Model\Rebill\Subscription;

use Exception;
use Improntus\Rebill\Model\Rebill\Subscription;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\DataObject;

class Model extends DataObject
{
    /**
     * @var Subscription
     */
    protected $subscription;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @param Subscription $subscription
     * @param ScopeConfigInterface $scopeConfig
     * @param array $data
     */
    public function __construct(
        Subscription         $subscription,
        ScopeConfigInterface $scopeConfig,
        array                $data = []
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->subscription = $subscription;
        parent::__construct($data);
    }

    /**
     * @param $email
     * @return void
     */
    public function load($email)
    {
        try {
            $item = $this->subscription->getSubscriptionFromClient($email);
            $this->setData([
                'id'               => $item['id'],
                'status'           => $item['status'],
                'quantity'         => $item['quantity'],
                'user_email'       => $item['userEmail'],
                'title'            => $item['title'],
                'last_charge_date' => $item['lastChargeDate'],
                'next_charge_date' => $item['nextChargeDate'],
            ]);
        } catch (Exception $exception) {}
    }
}
