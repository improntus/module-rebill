<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2022 Improntus (http://www.improntus.com/)
 * @package Improntus_Rebill
 */

namespace Improntus\Rebill\Plugin\Helper;

use Improntus\Rebill\Helper\Config;

class Address
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
     * @param \Magento\Customer\Helper\Address $subject
     * @param $result
     * @return bool|mixed
     */
    public function afterIsVatAttributeVisible(\Magento\Customer\Helper\Address $subject, $result)
    {
        $config = $this->configHelper->getCustomerAttributeForDocument();
        if ($config == 'vat_id') {
            return true;
        }
        return $result;
    }
}
