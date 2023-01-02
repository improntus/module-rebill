<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2022 Improntus (http://www.improntus.com/)
 * @package Improntus_Rebill
 */

namespace Improntus\Rebill\Model\Payment;

use Improntus\Rebill\Helper\Config;
use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Payment\Model\CcConfig;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\SalesRule\Model\ResourceModel\Rule\Collection;
use Magento\SalesRule\Model\ResourceModel\Rule\CollectionFactory;
use Magento\Store\Model\StoreManagerInterface;

class ConfigProvider implements ConfigProviderInterface
{
    /**
     * @var string
     */
    protected $methodCode = 'improntus_rebill';

    /**
     * @var CheckoutSession
     */
    protected $_checkoutSession;

    /**
     * @var CartRepositoryInterface
     */
    protected $_cart;

    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var CcConfig
     */
    protected $ccConfig;

    /**
     * @var CollectionFactory
     */
    protected $ruleCollectionFactory;

    /**
     * @var Config
     */
    protected $configHelper;

    /**
     * @param PaymentHelper $paymentHelper
     * @param CheckoutSession $checkoutSession
     * @param CartRepositoryInterface $cart
     * @param CcConfig $ccConfig
     * @param StoreManagerInterface $storeManager
     * @param Config $configHelper
     * @param CollectionFactory $ruleCollectionFactory
     * @throws LocalizedException
     */
    public function __construct(
        PaymentHelper           $paymentHelper,
        CheckoutSession         $checkoutSession,
        CartRepositoryInterface $cart,
        CcConfig                $ccConfig,
        StoreManagerInterface   $storeManager,
        Config                  $configHelper,
        CollectionFactory       $ruleCollectionFactory
    ) {
        $this->method = $paymentHelper->getMethodInstance($this->methodCode);

        $this->configHelper = $configHelper;
        $this->ruleCollectionFactory = $ruleCollectionFactory;
        $this->_checkoutSession = $checkoutSession;
        $this->_cart = $cart;
        $this->_storeManager = $storeManager;
        $this->ccConfig = $ccConfig;
    }

    /**
     * @return array|\array[][]
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getConfig()
    {
        return $this->method->isAvailable() ? [
            'payment' => [
                $this->methodCode => [
                    'actionUrl'        => $this->_storeManager->getStore()->getUrl('rebill/payment/transaction'),
                    'currency'         => '',
                    'country'          => '',
                    'method_available' => $this->isMethodAvailable(),
                ],
            ],
        ] : [];
    }

    /**
     * @return bool
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    protected function isMethodAvailable()
    {
        $methodAvailable = true;
        $quote = $this->_checkoutSession->getQuote();
        /** @var Collection $rules */
        $rules = $this->ruleCollectionFactory->create();
        $rules->addFieldToFilter('rule_id', explode(',', $quote->getAppliedRuleIds() ?? ''));
        $rulesMatched = true;
        foreach ($rules as $rule) {
            if (!$this->configHelper->cartRuleApplyToSubscriptionProducts($rule)) {
                $rulesMatched = false;
                break;
            }
        }
        if ($this->configHelper->hasQuoteSubscriptionProducts($quote) && !$rulesMatched) {
            $methodAvailable = false;
        }
        if ($this->configHelper->checkoutHasMixedCartConflict($quote)) {
            $methodAvailable = false;
        }
        if(!$this->configHelper->currencyAvailable()){
            $methodAvailable = false;
        }
        return $methodAvailable;
    }

    /**
     * @return bool
     */
    protected function hasVerification()
    {
        return true;
    }
}
