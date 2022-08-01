<?php

namespace Improntus\Rebill\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

class IntegrationMode implements ArrayInterface
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 'sandbox', 'label' => __('Sandbox')],
            ['value' => 'production', 'label' => __('Production')],
        ];
    }
}