<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2022 Improntus (http://www.improntus.com/)
 * @package Improntus_Rebill
 */

namespace Improntus\Rebill\Plugin\Catalog;

use Improntus\Rebill\Helper\Config;
use Magento\Framework\App\Area;
use Magento\Framework\Registry;
use Magento\Framework\App\State;

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
     * @var State
     */
    protected $state;
    /**
     * @param Config $configHelper
     * @param Registry $registry
     */
    public function __construct(
        Config   $configHelper,
        Registry $registry,
        State   $state
    ) {
        $this->registry = $registry;
        $this->configHelper = $configHelper;
        $this->state = $state;
    }

    /**
     * @param \Magento\Catalog\Model\Product $subject
     * @param $result
     * @return false|mixed
     */
    public function afterIsSalable(\Magento\Catalog\Model\Product $subject, $result)
    {
        if($this->state->getAreaCode() != Area::AREA_FRONTEND){
            return $result;
        }
        if (!$this->registry->registry('rebill_reorder_data')) {
            $subscriptionType = $subject->getData('rebill_subscription_type');
            if ($subscriptionType == 'subscription' && !$this->configHelper->isLoggedIn()) {
                $result = false;
            }
        }
        return $result;
    }
}
