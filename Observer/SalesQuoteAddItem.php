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
            $frequencyOption = $item->getOptionByCode('rebill_subscription');
            if ($frequencyOption) {
                $frequency = json_decode($frequencyOption->getValue(), true);
                if ($frequency) {
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
