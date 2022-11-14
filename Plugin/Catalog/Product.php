<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2022 Improntus (http://www.improntus.com/)
 * @package Improntus_Rebill
 */

namespace Improntus\Rebill\Plugin\Catalog;

use Improntus\Rebill\Helper\Config;
use Magento\Framework\Registry;

class Product
{
    /**
     * @var Config
     */
    protected $configHelper;

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @param Config $configHelper
     * @param Registry $registry
     */
    public function __construct(
        Config   $configHelper,
        Registry $registry
    ) {
        $this->registry = $registry;
        $this->configHelper = $configHelper;
    }

    /**
     * @param \Magento\Catalog\Model\Product $subject
     * @param $result
     * @return false|mixed
     */
    public function afterIsSalable(\Magento\Catalog\Model\Product $subject, $result)
    {
        if (!$this->registry->registry('rebill_reorder_data')) {
            $subscriptionType = $subject->getData('rebill_subscription_type');
            if ($subscriptionType == 'subscription' && !$this->configHelper->isLoggedIn()) {
                $result = false;
            }
        }
        return $result;
    }
}
