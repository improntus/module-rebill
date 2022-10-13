<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2022 Improntus (http://www.improntus.com/)
 * @package Improntus_Rebill
 */

namespace Improntus\Rebill\Observer;

use Exception;
use Improntus\Rebill\Helper\Config;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Quote\Model\Quote\Item;

class SalesQuoteAddItem implements ObserverInterface
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
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        try {
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
                    $price = $frequency['price'] + $product->getFinalPrice();
                    $item->setCustomPrice($price);
                    $item->setOriginalCustomPrice($price);
                    $item->getProduct()->setIsSuperMode(true);
                }
            }
        } catch (Exception $exception) {
            $this->configHelper->logError($exception->getMessage());
        }
    }
}
