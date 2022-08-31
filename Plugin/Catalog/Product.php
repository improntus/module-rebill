<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2022 Improntus (http://www.improntus.com/)
 * @package Improntus_Rebill
 */

namespace Improntus\Rebill\Plugin\Catalog;

use Improntus\Rebill\Helper\Config;

class Product
{
    /**
     * @var Config
     */
    protected $configHelper;

    /**
     * @param Config $configHelper
     */
    public function __construct(
        Config $configHelper
    ) {
        $this->configHelper = $configHelper;
    }

    /**
     * @param \Magento\Catalog\Model\Product $subject
     * @param $result
     * @return false|mixed
     */
    public function afterIsSalable(\Magento\Catalog\Model\Product $subject, $result)
    {
        $subscriptionType = $subject->getData('rebill_subscription_type');
        if ($subscriptionType == 'subscription' && !$this->configHelper->isLoggedIn()) {
            $result = false;
        }
        return $result;
    }
}