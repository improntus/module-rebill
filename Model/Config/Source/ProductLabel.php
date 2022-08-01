<?php

namespace Improntus\Rebill\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

class ProductLabel implements ArrayInterface
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 'none', 'label' => __('No product label')],
            ['value' => 'text', 'label' => __('Custom Text')],
            ['value' => 'rebill_logo', 'label' => __('Rebill Logo')],
        ];
    }
}
