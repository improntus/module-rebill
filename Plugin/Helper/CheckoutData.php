<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2022 Improntus (http://www.improntus.com/)
 * @package Improntus_Rebill
 */

namespace Improntus\Rebill\Plugin\Helper;

use Improntus\Rebill\Helper\Config;
use Magento\Checkout\Helper\Data;
use Magento\Checkout\Model\Session;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\ScopeInterface;

class CheckoutData
{
    /**
     * @var Config
     */
    protected $configHelper;

    /**
     * @var Session
     */
    protected $checkoutSession;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @param Config $configHelper
     * @param Session $checkoutSession
     * @param \Magento\Customer\Model\Session $customerSession
     */
    public function __construct(
        Config                          $configHelper,
        Session                         $checkoutSession,
        \Magento\Customer\Model\Session $customerSession
    ) {
        $this->customerSession = $customerSession;
        $this->checkoutSession = $checkoutSession;
        $this->configHelper = $configHelper;
    }

    /**
     * @param Data $subject
     * @param $result
     * @return false|mixed
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function afterCanOnepageCheckout(Data $subject, $result)
    {
        $quote = $this->checkoutSession->getQuote();
        if ($this->configHelper->hasQuoteSubscriptionProducts($quote)
            && !$this->customerSession->isLoggedIn()) {
            return false;
        }
        if ($this->configHelper->checkoutHasMixedCartConflict($quote)) {
            return false;
        }
        return $result;
    }
}
