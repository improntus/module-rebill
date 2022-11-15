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
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Pricing\Helper\Data as CurrencyHelper;
use Magento\Framework\Registry;
use Magento\Quote\Model\QuoteRepository;
use Magento\Customer\Model\SessionFactory;

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
    protected  $sessionFactory;

    /**
     * @param Context $context
     * @param Logger $logger
     * @param Session $customerSession
     * @param ProductFactory $productFactory
     * @param Registry $registry
     * @param CurrencyHelper $currencyHelper
     * @param Configurable $configurableType
     * @param QuoteRepository $quoteRepository
     */
    public function __construct(
        Context         $context,
        Logger          $logger,
        Session         $customerSession,
        ProductFactory  $productFactory,
        Registry        $registry,
        CurrencyHelper  $currencyHelper,
        Configurable    $configurableType,
        QuoteRepository $quoteRepository,
        SessionFactory  $sessionFactory
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->configurableType = $configurableType;
        $this->registry = $registry;
        $this->currencyHelper = $currencyHelper;
        $this->productFactory = $productFactory;
        $this->customerSession = $customerSession;
        $this->logger = $logger;
        $this->sessionFactory = $sessionFactory;
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
}
