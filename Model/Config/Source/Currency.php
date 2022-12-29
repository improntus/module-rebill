<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2022 Improntus (http://www.improntus.com/)
 * @package Improntus_Rebill
 */

namespace Improntus\Rebill\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;
use Improntus\Rebill\Model\Entity\Currency\Repository as CurrencyRepository;


class Currency implements ArrayInterface
{
    /**
     * @var array
     */
    protected $_options = [];
    /**
     * @var CurrencyRepository
     */
    protected $currencyRepository;

    /**
     * @param CurrencyRepository $currencyRepository
     */
    public function __construct(
        CurrencyRepository $currencyRepository
    ) {
        $this->currencyRepository = $currencyRepository;
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {
        $currencies = $this->currencyRepository->getCollection();
        foreach ($currencies as $item){
            $this->_options[] = [
                'value' => $item['entity_id'],
                'label' => "{$item['description']} - {$item['symbol']}",
            ];
        }

        return $this->_options ?? [];
    }
}
