<?php

namespace Improntus\Rebill\Model\Rebill\Subscription;

use Improntus\Rebill\Model\Rebill\Subscription;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\DataObject;

class Model extends DataObject
{
    protected $subscription;
    protected $scopeConfig;

    public function __construct(
        Subscription              $subscription,
        ScopeConfigInterface $scopeConfig,
        array                $data = []
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->subscription = $subscription;
        parent::__construct($data);
    }

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
        } catch (\Exception $exception) {

        }
    }
}
