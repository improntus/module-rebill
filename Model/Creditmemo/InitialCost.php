<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2022 Improntus (http://www.improntus.com/)
 * @package Improntus_Rebill
 */

namespace Improntus\Rebill\Model\Creditmemo;

use Improntus\Rebill\Helper\Config;
use Magento\Sales\Model\Order\Creditmemo;
use Magento\Sales\Model\Order\Total\AbstractTotal;
use Magento\Framework\Exception\NoSuchEntityException;

class InitialCost extends AbstractTotal
{
    /**
     * @var Config
     */
    protected $configHelper;

    /**
     * @param Config $configHelper
     * @param array $data
     */
    public function __construct(
        Config $configHelper,
        array  $data = []
    ) {
        $this->configHelper = $configHelper;
        parent::__construct($data);
    }

    /**
     * @param Creditmemo $creditmemo
     * @return $this
     * @throws NoSuchEntityException
     */
    public function collect(Creditmemo $creditmemo)
    {
        $order = $creditmemo->getOrder();
        $amount = $order->getData('rebill_initial_cost_amount');
        $baseAmount = $order->getData('base_rebill_initial_cost_amount');
        if ($amount) {
            $creditmemo->setData('rebill_initial_cost_amount', $amount);
            $creditmemo->setData('base_rebill_initial_cost_amount', $baseAmount);
            $creditmemo->setGrandTotal($creditmemo->getGrandTotal() + $amount);
            $creditmemo->setBaseGrandTotal($creditmemo->getBaseGrandTotal() + $baseAmount);
        } else {
            $rebillInfo = $this->configHelper->getOrderSubscriptionInformation($order);
            $cost = 0;
            foreach ($rebillInfo as $info) {
                $cost += $info['initialCost'];
            }
            $creditmemo->setData('rebill_initial_cost_amount', $cost);
            $creditmemo->setData('base_rebill_initial_cost_amount', $cost);
            $creditmemo->setGrandTotal($creditmemo->getGrandTotal() + $cost);
            $creditmemo->setBaseGrandTotal($creditmemo->getBaseGrandTotal() + $cost);
            $creditmemo->save();
        }

        return $this;
    }
}
