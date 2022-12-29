<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2022 Improntus (http://www.improntus.com/)
 * @package Improntus_Rebill
 */

namespace Improntus\Rebill\Helper;

use Exception;
use Improntus\Rebill\Logger\Logger;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ProductFactory;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Pricing\Helper\Data as CurrencyHelper;
use Magento\Framework\Registry;
use Magento\Quote\Model\QuoteRepository;
use Magento\Customer\Model\SessionFactory;
use Improntus\Rebill\Model\Entity\Currency\Repository as CurrencyRepository;
use Magento\Store\Model\StoreManagerInterface;

class Data extends AbstractHelper
{
    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var Session
     */
    protected $customerSession;

    /**
     * @var ProductFactory
     */
    protected $productFactory;

    /**
     * @var CurrencyHelper
     */
    protected $currencyHelper;

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var Configurable
     */
    protected $configurableType;

    /**
     * @var QuoteRepository
     */
    protected $quoteRepository;

    /**
     * @var SessionFactory
     */
    protected $sessionFactory;

    /**
     * @var CheckoutSession
     */
    protected $_checkoutSession;
    /**
     * @var CurrencyRepository
     */
    protected $currencyRepository;
    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @param Context $context
     * @param Logger $logger
     * @param Session $customerSession
     * @param ProductFactory $productFactory
     * @param Registry $registry
     * @param CurrencyHelper $currencyHelper
     * @param Configurable $configurableType
     * @param QuoteRepository $quoteRepository
     * @param SessionFactory $sessionFactory
     * @param CheckoutSession $checkoutSession
     * @param CurrencyRepository $currencyRepository
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Context               $context,
        Logger                $logger,
        Session               $customerSession,
        ProductFactory        $productFactory,
        Registry              $registry,
        CurrencyHelper        $currencyHelper,
        Configurable          $configurableType,
        QuoteRepository       $quoteRepository,
        SessionFactory        $sessionFactory,
        CheckoutSession       $checkoutSession,
        CurrencyRepository    $currencyRepository,
        StoreManagerInterface $storeManager
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->configurableType = $configurableType;
        $this->registry = $registry;
        $this->currencyHelper = $currencyHelper;
        $this->productFactory = $productFactory;
        $this->customerSession = $customerSession;
        $this->logger = $logger;
        $this->sessionFactory = $sessionFactory;
        $this->_checkoutSession = $checkoutSession;
        $this->currencyRepository = $currencyRepository;
        $this->storeManager = $storeManager;

        parent::__construct($context);
    }

    /**
     * @return bool
     */
    public function isLoggedIn()
    {
        $customer = $this->sessionFactory->create();
        return $customer->getCustomer()->getId() > 0;
    }

    /**
     * @param string $message
     */
    public function logInfo(string $message)
    {
        $this->logger->addInfo($message);
    }

    /**
     * @param string $message
     */
    public function logError(string $message)
    {
        $this->logger->addError($message);
    }

    /**
     * @param Product|null $product
     * @return bool
     */
    public function isProductChild(?Product $product)
    {
        try {
            if ($product->getTypeId() == 'configurable') {
                return false;
            } elseif ($product->getTypeId() == 'virtual' || $product->getTypeId() == 'simple') {
                if ($this->configurableType->getParentIdsByChild($product->getId())) {
                    return true;
                }
            }
        } catch (Exception $exception) {
            return false;
        }
        return false;
    }

    /**
     * @return bool
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function currencyAvailable($quote = null): bool
    {
        $quote = ($quote != null) ? $quote : $this->_checkoutSession->getQuote();
        if (!$this->hasQuoteSubscriptionProducts($quote)) {
            return true;
        }

        $currencies = $this->currencyRepository->getCollection();
        $baseCurrency = $this->storeManager->getStore()->getCurrentCurrencyCode();

        $result = $currencies->addFieldToSelect('*')
            ->addFieldToFilter('currency_id', ['eq' => $baseCurrency]);

        return $result->count() > 0;
    }

    /**
     * @param float $price
     * @return string
     */
    public function limitDecimal(float $price): string
    {
        $limitDecimalMax = 5;
        $price = round($this->returnNumberString($price), $limitDecimalMax);
        return (string)$price;
    }

    /**
     * @param $n
     * @param $min
     * @param $max
     * @return string
     */
    public function returnNumberString($n, $min = 20, $max = 20)
    {
        $abs = abs($n);
        if ($abs < 1) {
            $n = rtrim(sprintf("%.{$min}F", $n), "0");
        } else {
            $n = rtrim(rtrim(sprintf("%.{$max}F", $n), "0"), '.');
        }
        return $n;
    }
}
