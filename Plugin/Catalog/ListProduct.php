<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2022 Improntus (http://www.improntus.com/)
 * @package Improntus_Rebill
 */

namespace Improntus\Rebill\Plugin\Catalog;

use Improntus\Rebill\Helper\Config;

class ListProduct
{
    /**
     * @var Config
     */
    protected $configHelper;

    /**
     * @param Config $configHelper
     */
    public function __construct(
        Config   $configHelper
    ) {
        $this->configHelper = $configHelper;
    }

    /**
     * @param \Magento\Catalog\Block\Product\ListProduct $subject
     * @param $result
     * @return mixed
     */
    public function afterGetLoadedProductCollection(\Magento\Catalog\Block\Product\ListProduct $subject, $result)
    {
        foreach ($result->getItems() as $product) {
            if ($product->getRebillSubscriptionType() && $product->getRebillSubscriptionType() == "subscription") {
                $rebillDetails = $this->configHelper->getProductRebillSubscriptionDetails($product);
                if (0 == count($rebillDetails['frequency'])) {
                    $result->removeItemByKey($product->getEntityId());
                }
            }
        }

        return $result;
    }
}
