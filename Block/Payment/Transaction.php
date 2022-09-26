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

    public function getDocumentTypes()
    {
        return $this->customerDocumentType->toOptionArray();
    }
}
