<?php
namespace Improntus\Rebill\Block\Onepage;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Http\Context;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order\Config;

/**
 * Class Success
 *
 * @author Improntus <https://www.improntus.com>
 * @package Improntus\Rebill\Block\Onepage
 */
class Success extends \Magento\Checkout\Block\Onepage\Success
{
    /**
     * @var OrderInterface
     */
    protected $_order;

    /**
     * SuccessPending constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param Session $checkoutSession
     * @param Config $orderConfig
     * @param Context $httpContext
     * @param OrderInterface $order
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        Session $checkoutSession,
        Config $orderConfig,
        Context $httpContext,
        OrderInterface $order,
        array $data = []
    )
    {
        $this->_order = $order;

        parent::__construct($context, $checkoutSession, $orderConfig, $httpContext, $data);
    }

    /**
     * @return OrderInterface
     */
    public function getOrder()
    {
        $this->_order = $this->_order->loadByIncrementId($this->getOrderId());

        return $this->_order;
    }
}
