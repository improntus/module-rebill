<?php

namespace Improntus\Rebill\Observer;

use Improntus\Rebill\Helper\Config;
use Magento\Catalog\Model\Product;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class CheckoutCartProductAddBefore implements ObserverInterface
{
    protected $configHelper;

    public function __construct(
        Config $configHelper
    ) {
        $this->configHelper = $configHelper;
    }

    public function execute(Observer $observer)
    {
        /** @var Product $product */
        $product = $observer->getEvent()->getProduct();
        $info = $observer->getEvent()->getInfo();
        if (isset($info['frequency']) && $info['frequency'] && isset($info['use_subscription']) && $info['use_subscription'] == 1) {
            if (!$this->configHelper->isLoggedIn()) {
                throw new \Exception(__('Before trying to buy a subscription product, you have to be logged in first'));
            }
            $this->configHelper->setCurrentSubscription($info['frequency']);
            $frequencies = $this->configHelper->getProductRebillSubscriptionDetails($product)['frequency'];
            $frequency = [];
            foreach ($frequencies as $_frequency) {
                if ($_frequency['id'] == $info['frequency']) {
                    $frequency = $_frequency;
                }
            }
            $additionalOptions = $product->getData('additional_options') ?: [];
            $additionalOptions[] = [
                'value' => $this->configHelper->getFrequencyDescription($product, $frequency),
                'label' => __('Subscription')
            ];
            $product->addCustomOption('rebill_subscription', json_encode($frequency));
            $product->addCustomOption('additional_options', json_encode($additionalOptions));
        }
    }
}
