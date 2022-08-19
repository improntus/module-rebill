<?php

namespace Improntus\Rebill\Model\Invoice;

use Improntus\Rebill\Helper\Config;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order\Total\AbstractTotal;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Class FinanceCost
 *
 * @package Improntus\MercadoPago\Model\Invoice
 */
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
     * @param Invoice $invoice
     * @return $this
     * @throws NoSuchEntityException
     */
    public function collect(Invoice $invoice)
    {
        $order = $invoice->getOrder();
        $amount = $order->getData('rebill_initial_cost_amount');
        $baseAmount = $order->getData('base_rebill_initial_cost_amount');
        if ($amount) {
            $invoice->setData('rebill_initial_cost_amount', $amount);
            $invoice->setData('base_rebill_initial_cost_amount', $baseAmount);
            $invoice->setGrandTotal($invoice->getGrandTotal() + $amount);
            $invoice->setBaseGrandTotal($invoice->getBaseGrandTotal() + $baseAmount);
        } else {
            $rebillInfo = $this->configHelper->getOrderSubscriptionInformation($order);
            $cost = 0;
            foreach ($rebillInfo as $info) {
                $cost += $info['initialCost'];
            }
            $invoice->setData('rebill_initial_cost_amount', $cost);
            $invoice->setData('base_rebill_initial_cost_amount', $cost);
            $invoice->setGrandTotal($invoice->getGrandTotal() + $cost);
            $invoice->setBaseGrandTotal($invoice->getBaseGrandTotal() + $cost);
            $invoice->save();
        }

        return $this;
    }
}
