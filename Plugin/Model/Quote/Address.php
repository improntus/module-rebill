<?php

namespace Improntus\Rebill\Plugin\Model\Quote;

use Magento\Quote\Model\Quote\Address\RateFactory;

class Address
{
    /**
     * @var RateFactory
     */
    private $rateFactory;

    /**
     * @param RateFactory $rateFactory
     */
    public function __construct(
        RateFactory $rateFactory
    ) {
        $this->rateFactory = $rateFactory;
    }

    /**
     * @param \Magento\Quote\Model\Quote\Address $subject
     * @param $result
     * @return bool|mixed
     */
    public function afterGetShippingRateByCode(\Magento\Quote\Model\Quote\Address $subject, $result)
    {
        if (($rebillReorder = $subject->getData('rebill_reorder')) && !$result) {
            $rate = $this->rateFactory->create();
            $rate->setCarrier('flatrate');
            $rate->setMethod('flatrate');
            $rate->setCarrierTitle('Rebill');
            $rate->setMethodTitle('The original shipping method wasn\'t available.');
            $rate->setPrice($rebillReorder['shipping_costs']['shipping_amount']);
            return $rate;
        }
        return $result;
    }
}
