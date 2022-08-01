<?php

namespace Improntus\Rebill\Plugin\Catalog;

use Improntus\Rebill\Helper\Config;

class Product
{
    protected $configHelper;

    public function __construct(
        Config $configHelper
    ) {
        $this->configHelper = $configHelper;
    }

    public function afterIsSalable(\Magento\Catalog\Model\Product $subject, $result)
    {
        $subscriptionType = $subject->getData('rebill_subscription_type');
        if ($subscriptionType == 'subscription' && !$this->configHelper->isLoggedIn()) {
            $result = false;
        }
        return $result;
    }
}
