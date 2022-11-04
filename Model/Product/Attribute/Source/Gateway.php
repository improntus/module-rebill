<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2022 Improntus (http://www.improntus.com/)
 * @package Improntus_Rebill
 */

namespace Improntus\Rebill\Model\Product\Attribute\Source;

use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;

class Gateway extends AbstractSource
{
    /**
     * @var \Improntus\Rebill\Model\Rebill\Gateway
     */
    protected $gateway;

    /**
     * @param \Improntus\Rebill\Model\Rebill\Gateway $gateway
     */
    public function __construct(
        \Improntus\Rebill\Model\Rebill\Gateway $gateway
    ) {
        $this->gateway = $gateway;
    }

    /**
     * @return array|null
     */
    public function getAllOptions()
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
        return $this->_options;
    }
}
