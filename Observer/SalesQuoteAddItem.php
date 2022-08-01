<?php

namespace Improntus\Rebill\Observer;

use Improntus\Rebill\Helper\Config;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Quote\Model\Quote\Item;

class SalesQuoteAddItem implements ObserverInterface
{
    protected $configHelper;

    public function __construct(
        Config $configHelper
    ) {
        $this->configHelper = $configHelper;
    }

    public function execute(Observer $observer)
    {
        /** @var Item $item */
        $item = $observer->getEvent()->getData('quote_item');
        $product = $item->getProduct();
        if ($subscriptionId = $this->configHelper->getCurrentSubscription()) {
            $frequencies = $this->configHelper->getProductRebillSubscriptionDetails($product)['frequency'];
            if ($frequencies) {
                $frequency = [];
                foreach ($frequencies as $_frequency) {
                    if ($_frequency['id'] == $subscriptionId) {
                        $frequency = $_frequency;
                    }
                }
                $price = $frequency['price'];
                if ($product->getTypeId() == 'configurable' && !(int)$product->getData('rebill_individual_settings_in_simple')) {
                    $price += $product->getFinalPrice();
                }
                $item->setCustomPrice($price);
                $item->setOriginalCustomPrice($price);
                $item->getProduct()->setIsSuperMode(true);
            }
        }
    }
}
