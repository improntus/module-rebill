<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2022 Improntus (http://www.improntus.com/)
 * @package Improntus_Rebill
 */

namespace Improntus\Rebill\Model\Quote;

use Improntus\Rebill\Helper\Config;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\RequestInterface;
use Magento\Quote\Api\Data\ShippingAssignmentInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address\Total;
use Magento\Quote\Model\Quote\Address\Total\AbstractTotal;

class InitialCost extends AbstractTotal
{
    /**
     * @var Session
     */
    protected $_checkoutSession;

    /**
     * @var RequestInterface
     */
    protected $_request;

    /**
     * @var Config
     */
    protected $configHelper;

    /**
     * @param Session $checkoutSession
     * @param RequestInterface $request
     * @param Config $configHelper
     */
    public function __construct(
        Session          $checkoutSession,
        RequestInterface $request,
        Config           $configHelper
    ) {
        $this->setCode('rebill_initial_cost');
        $this->configHelper = $configHelper;
        $this->_checkoutSession = $checkoutSession;
        $this->_request = $request;
    }

    /**
     * @param Quote $quote
     * @param ShippingAssignmentInterface $shippingAssignment
     * @param Total $total
     * @return $this|InitialCost
     */
    public function collect(
        Quote                       $quote,
        ShippingAssignmentInterface $shippingAssignment,
        Total                       $total
    ) {
        $address = $shippingAssignment->getShipping()->getAddress();

        parent::collect($quote, $shippingAssignment, $total);

        $subscriptionInformation = $this->configHelper->getQuoteSubscriptionInformation($quote);

        $cost = 0;

        foreach ($subscriptionInformation as $info) {
            $cost += (float)$info['initialCost'];
        }

        $address->setData('rebill_initial_cost_amount', $cost);
        $address->setData('base_rebill_initial_cost_amount', $cost);

        $total->setRebillInitialCostDescription($this->getCode());
        $total->setRebillInitialCostAmount($cost);
        $total->setBaseInitialCostAmount($cost);

        $total->addTotalAmount($this->getCode(), $cost);
        $total->addBaseTotalAmount($this->getCode(), $cost);

        return $this;
    }

    /**
     * @param Quote $quote
     * @param Total $total
     * @return array
     */
    public function fetch(Quote $quote, Total $total)
    {
        $amount = $total->getRebillInitialCostAmount();
        return [
            'code'  => $this->getCode(),
            'title' => __('Subscription Initial Cost'),
            'value' => $amount
        ];
    }
}
