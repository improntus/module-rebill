<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2022 Improntus (http://www.improntus.com/)
 * @package Improntus_Rebill
 */

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
