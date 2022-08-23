<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2022 Improntus (http://www.improntus.com/)
 * @package Improntus_Rebill
 */

namespace Improntus\Rebill\Block\Sales\Order\Totals;

use Improntus\Rebill\Helper\Config;
use Magento\Framework\DataObject;
use Magento\Framework\View\Element\Template;
use Magento\Sales\Model\Order\Payment;
use Magento\Framework\Exception\NoSuchEntityException;

class InitialCost extends Template
{
    /**
     * @var Config
     */
    protected $configHelper;

    /**
     * @param Template\Context $context
     * @param Config $configHelper
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        Config           $configHelper,
        array            $data = []
    ) {
        $this->configHelper = $configHelper;
        parent::__construct($context, $data);
    }

    /**
     * @return mixed
     */
    public function getSource()
    {
        return $this->getParentBlock()->getSource();
    }

    /**
     * @return $this
     * @throws NoSuchEntityException
     * @description add rebill costs to total orders and calculate them if they don't exist
     */
    public function initTotals()
    {
        $order = $this->getSource();
        $orderPayment = $order->getPayment();

        if ($orderPayment instanceof Payment && $this->configHelper->hasOrderSubscriptionProducts($order)) {
            if ($order->getData('rebill_initial_cost_amount') > 0) {
                $total = new DataObject([
                    'code'  => 'rebill_initial_cost',
                    'field' => 'rebill_initial_cost_amount',
                    'value' => $order->getData('rebill_initial_cost_amount'),
                    'label' => __('Subscription Initial Cost'),
                ]);
                $parent = $this->getParentBlock();
                $parent->addTotalBefore($total, 'shipping');
            } else {
                $rebillInfo = $this->configHelper->getOrderSubscriptionInformation($order);
                $cost = 0;
                foreach ($rebillInfo as $info) {
                    $cost += $info['initialCost'];
                }
                $order->setData('rebill_initial_cost_amount', $cost);
                $order->setData('base_rebill_initial_cost_amount', $cost);
                $order->save();
                $total = new DataObject([
                    'code'  => 'rebill_initial_cost',
                    'field' => 'rebill_initial_cost_amount',
                    'value' => $cost,
                    'label' => __('Subscription Initial Cost'),
                ]);
                $parent = $this->getParentBlock();
                $parent->addTotalBefore($total, 'shipping');
            }
        }

        return $this;
    }
}
