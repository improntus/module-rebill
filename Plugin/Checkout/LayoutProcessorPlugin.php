<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2022 Improntus (http://www.improntus.com/)
 * @package Improntus_Rebill
 */

namespace Improntus\Rebill\Plugin\Checkout;

use Improntus\Rebill\Helper\Config;
use Magento\Checkout\Block\Checkout\LayoutProcessor;
use Magento\Checkout\Model\Session;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

class LayoutProcessorPlugin
{
    /**
     * @var Session
     */
    protected $session;

    /**
     * @var Config
     */
    protected $configHelper;

    /**
     * @param Session $session
     * @param Config $configHelper
     */
    public function __construct(
        Session $session,
        Config  $configHelper
    ) {
        $this->session = $session;
        $this->configHelper = $configHelper;
    }

    /**
     * @param LayoutProcessor $subject
     * @param array $result
     * @return array
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function afterProcess(LayoutProcessor $subject, array $result)
    {
        /**
         * In case of buying subscription products, any other payment method has to be removed
         * Because otherwise, the customer could pay with a platform
         */
        $c = 'children';
        //phpcs:disable
        $paymentRenderers = &$result['components']['checkout'][$c]['steps'][$c]['billing-step'][$c]['payment'][$c]['renders'][$c];
        //phpcs:enable
        $quote = $this->session->getQuote();
        foreach ($paymentRenderers as $renderName => $paymentRenderer) {
            if ($renderName != 'improntus_rebill-payment') {
                if ($this->configHelper->hasQuoteSubscriptionProducts($quote)) {
                    unset($paymentRenderers[$renderName]);
                }
            }
        }
        return $result;
    }
}
