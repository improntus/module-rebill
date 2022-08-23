<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2022 Improntus (http://www.improntus.com/)
 * @package Improntus_Rebill
 */

namespace Improntus\Rebill\Block\Payment;

use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;
use Magento\Sales\Model\Order;

class Transaction extends Template
{
    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @param Template\Context $context
     * @param Registry $registry
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        Registry         $registry,
        array            $data = []
    ) {
        parent::__construct($context, $data);
        $this->registry = $registry;
    }

    /**
     * @return mixed|null
     */
    public function getOrder()
    {
        return $this->registry->registry('prepared_order');
    }

    /**
     * @return mixed|null
     */
    public function getPrices()
    {
        return $this->registry->registry('rebill_prices');
    }
}
