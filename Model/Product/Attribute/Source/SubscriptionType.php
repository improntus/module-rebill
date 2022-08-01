<?php

namespace Improntus\Rebill\Model\Product\Attribute\Source;

use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;

class SubscriptionType extends AbstractSource
{
    /**
     * @return array|null
     */
    public function getAllOptions()
    {
        if (!$this->_options) {
            $this->_options = [
                ['value' => null, 'label' => __('One-time only')],
                ['value' => 'mixed', 'label' => __('One-time and Subscription')],
                ['value' => 'subscription', 'label' => __('Subscription Only')],
            ];
        }
        return $this->_options;
    }
}
