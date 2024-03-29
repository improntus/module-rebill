<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2022 Improntus (http://www.improntus.com/)
 * @package Improntus_Rebill
 */

namespace Improntus\Rebill\Plugin\Model;

use Exception;
use Magento\Framework\DataObject;
use Magento\Catalog\Model\Product;
use Improntus\Rebill\Helper\Config;
use Magento\Quote\Model\Quote as Subject;

class Quote
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
     * @param Subject $subject
     * @param Product $product
     * @param mixed $request
     * @return array
     */
    public function beforeAddProduct(Subject $subject, Product $product, $request = null)
    {

        if (!($request instanceof DataObject)) {
            return [$product, $request];
        }
        try {
            $info = $request->getData();
            if (isset($info['frequency']) && $info['frequency']
                && isset($info['use_subscription']) && $info['use_subscription'] == 1) {
                $this->configHelper->setCurrentSubscription($info['frequency']);
                if (is_array($info['frequency'])) {
                    $frequency = $info['frequency'];
                } else {
                    $frequencies = $this->configHelper->getProductRebillSubscriptionDetails($product)['frequency'];
                    $frequency = [];
                    foreach ($frequencies as $_frequency) {
                        if ($_frequency['id'] == $info['frequency']) {
                            $frequency = $_frequency;
                        }
                    }
                }
                $additionalOptions = $product->getData('additional_options') ?: [];
                $additionalOptions[] = [
                    'value' => $this->configHelper->getFrequencyDescription($product, $frequency),
                    'label' => __('Subscription'),
                ];
                $product->addCustomOption('rebill_subscription', json_encode($frequency));
                $product->addCustomOption('additional_options', json_encode($additionalOptions));
            }
        } catch (Exception $exception) {
            $this->configHelper->logError($exception->getMessage());
        }
        return [$product, $request];
    }
}
