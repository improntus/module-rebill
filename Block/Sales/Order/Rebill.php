<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2022 Improntus (http://www.improntus.com/)
 * @package Improntus_Rebill
 */

namespace Improntus\Rebill\Block\Sales\Order;

use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template\Context;
use Magento\Payment\Block\Info;
use Magento\Sales\Api\Data\OrderInterface;

class Rebill extends Info
{
    /**
     * @var string
     */
    protected $_template = 'Improntus_Rebill::info/rebill.phtml';

    /**
     * @var Registry
     */
    protected $_coreRegistry;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param array $data
     */
    public function __construct(
        Context  $context,
        Registry $registry,
        array    $data = []
    ) {
        $this->_coreRegistry = $registry;
        parent::__construct($context, $data);
    }

    /**
     * @return OrderInterface
     */
    public function getCurrentOrder()
    {
        return $this->_coreRegistry->registry('current_order');
    }
}
