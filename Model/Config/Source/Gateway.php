<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2022 Improntus (http://www.improntus.com/)
 * @package Improntus_Rebill
 */

namespace Improntus\Rebill\Model\Config\Source;

use Improntus\Rebill\Model\Rebill;
use Magento\Framework\Option\ArrayInterface;

class Gateway implements ArrayInterface
{
    /**
     * @var array
     */
    protected $_options = [];

    /**
     * @var Rebill\Gateway
     */
    protected $gateway;

    /**
     * @param Rebill\Gateway $gateway
     */
    public function __construct(
        Rebill\Gateway $gateway
    ) {
        $this->gateway = $gateway;
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {
        if (!$this->_options) {
            $gateways = $this->gateway->getGateways();
            $this->_options = [];
            $this->_options[] = [
                'value' => null,
                'label' => __('None selected'),
            ];
            foreach ($gateways as $item) {
                if ($item['status'] != 'ACCEPTED') {
                    continue;
                }
                $this->_options[] = [
                    'value' => $item['id'],
                    'label' => "{$item['type']} - {$item['country']} - {$item['description']}",
                ];
            }
        }
        return $this->_options ?? [];
    }
}
