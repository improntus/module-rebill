<?php
namespace Improntus\Rebill\Block\Sales\Order;

use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template\Context;
use Magento\Payment\Block\Info;
use Magento\Sales\Api\Data\OrderInterface;

/**
 * Class Rebill
 *
 * @author Improntus <https://www.improntus.com>
 * @package Improntus\Rebill\Block\Sales\Order
 */
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
     * OfflinePayment constructor.
     * @param Context $context
     * @param array $data
     * @param Registry $registry
     */
    public function __construct(
        Context $context,
        Registry $registry,
        array $data = []
    )
    {
        $this->_coreRegistry = $registry;

        parent::__construct($context, $data);
    }

    /**
     * @return OrderInterface
     */
    public function getCurrentOrder()
    {
        return  $this->_coreRegistry->registry('current_order');
    }
}
