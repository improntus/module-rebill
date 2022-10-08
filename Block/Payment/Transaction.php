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
use Improntus\Rebill\Model\Config\Source\CustomerDocumentType;

class Transaction extends Template
{
    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var CustomerDocumentType
     */
    protected $customerDocumentType;

    /**
     * @var Order
     */
    protected $order;

    /**
     * @var array
     */
    protected $prices;

    /**
     * @param Template\Context $context
     * @param Registry $registry
     * @param CustomerDocumentType $customerDocumentType
     * @param array $data
     */
    public function __construct(
        Template\Context     $context,
        Registry             $registry,
        CustomerDocumentType $customerDocumentType,
        array                $data = []
    ) {
        parent::__construct($context, $data);
        $this->registry = $registry;
        $this->customerDocumentType = $customerDocumentType;
    }

    /**
     * @param Order $order
     * @return $this
     */
    public function setOrder(Order $order)
    {
        $this->order = $order;
        return $this;
    }

    /**
     * @param array $prices
     * @return $this
     */
    public function setPrices(array $prices)
    {
        $this->prices = $prices;
        return $this;
    }

    /**
     * @return Order
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * @return array
     */
    public function getPrices()
    {
        return $this->prices;
    }

    /**
     * @return array
     */
    public function getDocumentTypes()
    {
        return $this->customerDocumentType->toOptionArray();
    }
}
