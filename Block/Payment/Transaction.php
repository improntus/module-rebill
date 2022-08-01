<?php

namespace Improntus\Rebill\Block\Payment;

use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;
use Magento\Sales\Model\Order;

class Transaction extends Template
{
    protected $registry;

    public function __construct(
        Template\Context $context,
        Registry         $registry,
        array            $data = []
    ) {
        parent::__construct($context, $data);
        $this->registry = $registry;
    }

    /**
     * @return Order
     */
    public function getOrder()
    {
        return $this->registry->registry('prepared_order');
    }

    /**
     * @return array
     */
    public function getPrices()
    {
        return $this->registry->registry('rebill_prices');
    }
}
