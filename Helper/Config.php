<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2022 Improntus (http://www.improntus.com/)
 * @package Improntus_Rebill
 */

namespace Improntus\Rebill\Helper;

use Magento\Store\Model\ScopeInterface;

class Config extends Subscription
{
    /**
     * @return bool
     */
    public function isEnabled()
    {
        return (bool)$this->getPaymentConfig('active');
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return (string)$this->getPaymentConfig('title');
    }

    /**
     * @return int
     */
    public function getSortOrder()
    {
        return (int)$this->getPaymentConfig('sort_order');
    }

    /**
     * @return string
     */
    public function getCustomerAttributeForDocument()
    {
        return (string)$this->getPaymentConfig('general/customer_document_attribute');
    }

    /**
     * @return string
     */
    public function getCustomerDocumentType()
    {
        return (string)$this->getPaymentConfig('general/customer_document_type');
    }

    /**
     * @return string
     */
    public function getApprovedStatus()
    {
        return (string)$this->getPaymentConfig('general/approved_status');
    }

    /**
     * @return string
     */
    public function getDeniedStatus()
    {
        return (string)$this->getPaymentConfig('general/denied_status');
    }

    /**
     * @return bool
     */
    public function isMixedCartAllowed()
    {
        return (bool)$this->getPaymentConfig('general/allow_mixed_cart');
    }

    /**
     * @return bool
     */
    public function isEnqueueWebhooksEnabled()
    {
        return (bool)$this->getPaymentConfig('general/enqueue_webhooks');
    }

    /**
     * @return string
     */
    public function getSubscriptionProductText()
    {
        return (string)$this->getPaymentConfig('general/subscription_product_text');
    }

    /**
     * @return string
     */
    public function getGuestCustomerText()
    {
        return (string)$this->getPaymentConfig('general/guest_customer_text');
    }

    /**
     * @return string
     */
    public function getProductLabel()
    {
        return (string)$this->getPaymentConfig('general/product_label');
    }

    /**
     * @return string
     */
    public function getProductLabelCustomText()
    {
        return (string)$this->getPaymentConfig('general/product_label_text');
    }

    /**
     * @return string
     */
    public function getGatewayId()
    {
        return (string)$this->getPaymentConfig('api_options/default_gateway');
    }

    /**
     * @return string
     */
    public function getApiUser()
    {
        return (string)$this->getPaymentConfig('api_options/user_email');
    }

    /**
     * @return string
     */
    public function getApiPassword()
    {
        return (string)$this->getPaymentConfig('api_options/password');
    }

    /**
     * @return string
     */
    public function getApiAlias()
    {
        return (string)$this->getPaymentConfig('api_options/alias');
    }

    /**
     * @return string
     */
    public function getApiUuid()
    {
        return (string)$this->getPaymentConfig('api_options/uuid');
    }

    /**
     * @return string
     */
    public function getIntegrationMode()
    {
        return (string)$this->getPaymentConfig('api_options/integration_mode');
    }

    /**
     * @return bool
     */
    public function isDebugLogsEnabled()
    {
        return (bool)$this->getPaymentConfig('api_options/debug_logs');
    }

    /**
     * @return string
     */
    public function getCountry()
    {
        $result = (string)$this->getConfig('general/country/default');
        return $result == 'GB' ? 'UK' : $result;
    }

    /**
     * @return string
     */
    public function getCurrency()
    {
        return (string)$this->getConfig('currency/options/base');
    }

    /**
     * @param string $path
     * @return mixed
     */
    protected function getConfig(string $path)
    {
        return $this->scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE);
    }

    /**
     * @param string $path
     * @return mixed
     */
    protected function getPaymentConfig(string $path)
    {
        return $this->scopeConfig->getValue("payment/improntus_rebill/$path", ScopeInterface::SCOPE_STORE);
    }

    /**
     * @param string $message
     */
    public function logInfo(string $message)
    {
        if ($this->isDebugLogsEnabled()) {
            parent::logInfo($message);
        }
    }

    /**
     * @param string $message
     */
    public function logError(string $message)
    {
        if ($this->isDebugLogsEnabled()) {
            parent::logError($message);
        }
    }
}
