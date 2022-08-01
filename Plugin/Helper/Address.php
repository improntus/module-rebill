<?php

namespace Improntus\Rebill\Plugin\Helper;

use Improntus\Rebill\Helper\Config;

class Address
{
    protected $configHelper;

    public function __construct(
        Config $configHelper
    ) {
        $this->configHelper = $configHelper;
    }

    public function afterIsVatAttributeVisible(\Magento\Customer\Helper\Address $subject, $result)
    {
        $config = $this->configHelper->getCustomerAttributeForDocument();
        if ($config == 'vat_id') {
            return true;
        }
        return $result;
    }
}
