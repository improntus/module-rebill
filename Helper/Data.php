<?php

namespace Improntus\Rebill\Helper;

use Exception;
use Improntus\Rebill\Logger\Logger;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ProductFactory;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Registry;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item\OptionFactory;
use Magento\Sales\Model\Order;
use Magento\SalesRule\Model\Rule;
use Magento\Framework\Pricing\Helper\Data as CurrencyHelper;

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
    protected $currencyHelper;
    protected $registry;
    protected $configurableType;

    /**
     * @param Context $context
     * @param Logger $logger
     * @param Session $customerSession
     * @param ProductFactory $productFactory
     * @param Registry $registry
     * @param CurrencyHelper $currencyHelper
     * @param Configurable $configurableType
     */
    public function __construct(
        Context        $context,
        Logger         $logger,
        Session        $customerSession,
        ProductFactory $productFactory,
        Registry       $registry,
        CurrencyHelper $currencyHelper,
        Configurable   $configurableType
    ) {
        $this->configurableType = $configurableType;
        $this->registry = $registry;
        $this->currencyHelper = $currencyHelper;
        $this->productFactory = $productFactory;
        $this->customerSession = $customerSession;
        $this->logger = $logger;
        parent::__construct($context);
    }

    /**
     * @return bool
     */
    public function isLoggedIn()
    {
        return $this->customerSession->isLoggedIn();
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
