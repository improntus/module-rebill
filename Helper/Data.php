<?php

namespace Improntus\Rebill\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Zend\Log\Logger;
use Zend\Log\Writer\Stream;

/**
 * Class Data
 *
 * @author Improntus <https://www.improntus.com>
 * @package Improntus\Rebill\Helper
 */
class Data extends AbstractHelper
{
    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * Data constructor.
     * @param Context $context
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager
    )
    {
        $this->_storeManager = $storeManager;

        parent::__construct($context);
    }

    /**
     * @return bool
     */
    public function getSandboxMode()
    {
        return (boolean)$this->scopeConfig->getValue('payment/improntus_rebillsmartfields/sandboxmode', ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return bool
     */
    public function getSmartFieldsEnabled()
    {
        return (boolean)$this->scopeConfig->getValue('payment/improntus_rebillsmartfields/active', ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return bool
     */
    public function getTicketEnabled()
    {
        return (boolean)$this->scopeConfig->getValue('payment/improntus_rebillticket/active', ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return bool
     */
    public function getBanktransferEnabled()
    {
        return (boolean)$this->scopeConfig->getValue('payment/improntus_rebillbanktransfer/active', ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return string
     */
    public function getApiKey()
    {
        return $this->scopeConfig->getValue('payment/improntus_rebillsmartfields/api_key', ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return string
     */
    public function getXLogin()
    {
        return $this->scopeConfig->getValue('payment/improntus_rebillsmartfields/x_login', ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return string
     */
    public function getCountry()
    {
        return $this->scopeConfig->getValue('payment/improntus_rebillsmartfields/country', ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return bool
     */
    public function getDebugMode()
    {
        return (boolean)$this->scopeConfig->getValue('payment/improntus_rebillsmartfields/enable_debug', ScopeInterface::SCOPE_STORE);
    }

    /**
     * @param $method
     * @return string
     */
    public function getApiUrl(string $method)
    {
        return $this->getSandboxMode() ? "https://sandbox.rebill.com/$method" : "https://api.rebill.com/$method";
    }

    /**
     * @return string
     */
    public function getCurrency()
    {
//        $countryData = $this->getCountryData($this->getCountry());
//
//        if(is_array($countryData))
//        {
//            return $countryData['currency'];
//        }
//        else
//            return 'USD';
    }

    /**
     * @param $message String
     * @param $fileName String
     */
    public static function log($message,$fileName)
    {

        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/'.$fileName);
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
        $logger->info($message);
    }
}
