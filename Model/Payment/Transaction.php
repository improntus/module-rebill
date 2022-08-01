<?php

namespace Improntus\Rebill\Model\Payment;

use Exception;
use Improntus\Rebill\Helper\Config;
use Improntus\Rebill\Model\ItemFactory;
use Improntus\Rebill\Model\Price;
use Improntus\Rebill\Model\PriceFactory;
use Magento\Checkout\Model\Session;
use Improntus\Rebill\Model\Rebill;
use Magento\Framework\Registry;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Item;

class Transaction
{
    /**
     * @var Config
     */
    protected $configHelper;

    /**
     * @var Session
     */
    protected $session;

    /**
     * @var Rebill\Item
     */
    protected $item;

    /**
     * @var ItemFactory
     */
    protected $itemFactory;

    /**
     * @var PriceFactory
     */
    protected $priceFactory;

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @param Config $configHelper
     * @param Session $session
     * @param Rebill\Item $item
     * @param ItemFactory $itemFactory
     * @param PriceFactory $priceFactory
     * @param Registry $registry
     */
    public function __construct(
        Config       $configHelper,
        Session      $session,
        Rebill\Item  $item,
        ItemFactory  $itemFactory,
        PriceFactory $priceFactory,
        Registry     $registry
    ) {
        $this->registry = $registry;
        $this->configHelper = $configHelper;
        $this->session = $session;
        $this->item = $item;
        $this->itemFactory = $itemFactory;
        $this->priceFactory = $priceFactory;
    }

    /**
     * @param Order|null $order
     * @return bool
     * @throws Exception
     */
    public function prepareTransaction(Order $order = null)
    {
        try {
            if (!$order) {
                $order = $this->session->getLastRealOrder();
            }
            if (!$order->getId() || $order->getPayment()->getMethod() !== \Improntus\Rebill\Model\Payment\Rebill::CODE) {
                return false;
            }
            $this->registry->register('prepared_order', $order);
            $this->registry->register('current_order', $order);
            $items = $order->getAllItems();
            $prices = [];
            /** @var Item $item */
            foreach ($items as $item) {
                if (!$item->getParentItemId()) {
                    continue;
                }
                $rebillPriceId = $this->getRebillPriceIdForItem($item);
                $prices[] = [
                    'id'       => $rebillPriceId,
                    'quantity' => (int)$item->getQtyOrdered()
                ];
            }
            if ($id = $this->getRebillPriceIdForShipping($order)) {
                $prices[] = [
                    'id'       => $id,
                    'quantity' => 1
                ];
            }
            if ($id = $this->getRebillPriceIdForAdditionalCosts($order)) {
                $prices[] = [
                    'id'       => $id,
                    'quantity' => 1
                ];
            }
            $this->registry->register('rebill_prices', $prices);
        } catch (Exception $exception) {
            $this->session->restoreQuote();
            throw $exception;
        }
        return true;
    }

    protected function getRebillPriceIdForAdditionalCosts(Order $order)
    {
        $total = $order->getGrandTotal()
            - $order->getShippingAmount()
            - $order->getShippingTaxAmount();
        /** @var Item $item */
        foreach ($order->getAllVisibleItems() as $item) {
            $total -= $item->getRowTotal();
            $rebillSubscription = json_decode($item->getProductOptionByCode('rebill_subscription'), true);
            $total += $rebillSubscription['initialCost'] * $item->getQtyOrdered();
        }
        $total += $order->getTaxAmount();
        if (!$total) {
            return null;
        }
        /** @var \Improntus\Rebill\Model\Item $rebillItem */
        $rebillItem = $this->itemFactory->create();
        $rebillItem->load("Pedido #{$order->getIncrementId()}", 'product_sku');
        if (!$rebillItem->getId()) {
            $rebillItem->setData('product_sku', $order->getShippingDescription());
            $rebillItemId = $this->item->createItem([
                'name'        => "Pedido #{$order->getIncrementId()}",
                'description' => "Pedido #{$order->getIncrementId()}"
            ]);
            if ($rebillItemId === null) {
                throw new Exception(__('Unable to create items on Rebill.'));
            }
            $rebillItem->setData('rebill_item_id', $rebillItemId);
            $rebillItem->setData('product_description', $order->getShippingDescription());
            $rebillItem->save();
        }
        $rebillItemId = $rebillItem->getData('rebill_item_id');
        //@TODO in future implementation it will be needed the gateway in $rebillDetails
        $gateway = $this->configHelper->getGatewayId();
        $hash = hash('md5', $total . $order->getShippingDescription() . $gateway);
        /** @var Price $rebillPrice */
        $rebillPrice = $this->priceFactory->create();
        $rebillPrice->load($hash, 'details_hash');
        if (!$rebillPrice->getId()) {
            $rebillPriceData = [
                'amount'      => (string)$total,
                'type'        => 'fixed',
                'repetitions' => 1,
                'currency'    => $this->configHelper->getCurrency(),
                'gatewayId'   => $gateway,
                'enabled'     => true
            ];
            $rebillPriceId = $this->item->createPriceForItem($rebillItemId, $rebillPriceData);
            if ($rebillPriceId === null) {
                throw new Exception(__('Unable to create prices on Rebill.'));
            }
            $rebillPrice->setData([
                'rebill_item_id'  => $rebillItem->getId(),
                'rebill_price_id' => $rebillPriceId,
                'details'         => json_encode([]),
                'details_hash'    => $hash,
                'order_id'        => $order->getId(),
                'order_item_id'   => null
            ]);
            $rebillPrice->save();
        }
        return $rebillPrice->getData('rebill_price_id');
    }

    protected function getRebillPriceIdForShipping(Order $order)
    {
        if (!$order->getShippingAmount()) {
            return null;
        }
        /** @var \Improntus\Rebill\Model\Item $rebillItem */
        $rebillItem = $this->itemFactory->create();
        $rebillItem->load($order->getShippingDescription(), 'product_sku');
        if (!$rebillItem->getId()) {
            $rebillItem->setData('product_sku', $order->getShippingDescription());
            $rebillItemId = $this->item->createItem([
                'name'        => $order->getShippingDescription(),
                'description' => $order->getShippingDescription()
            ]);
            if ($rebillItemId === null) {
                throw new Exception(__('Unable to create items on Rebill.'));
            }
            $rebillItem->setData('rebill_item_id', $rebillItemId);
            $rebillItem->setData('product_description', $order->getShippingDescription());
            $rebillItem->save();
        }
        $rebillItemId = $rebillItem->getData('rebill_item_id');
        $rowTotal = $order->getShippingAmount()
            - $order->getShippingDiscountAmount()
            + $order->getShippingTaxAmount()
            + $order->getShippingDiscountTaxCompensationAmount();
        $itemQty = 1;
        $price = $rowTotal / $itemQty;
        //@TODO in future implementation it will be needed the gateway in $rebillDetails
        $gateway = $this->configHelper->getGatewayId();
        $hash = hash('md5', $price . $order->getShippingDescription() . $gateway);
        /** @var Price $rebillPrice */
        $rebillPrice = $this->priceFactory->create();
        $rebillPrice->load($hash, 'details_hash');
        if (!$rebillPrice->getId()) {
            $rebillPriceData = [
                'amount'      => (string)$price,
                'type'        => 'fixed',
                'repetitions' => 1,
                'currency'    => $this->configHelper->getCurrency(),
                'gatewayId'   => $gateway,
                'enabled'     => true
            ];
            $rebillPriceId = $this->item->createPriceForItem($rebillItemId, $rebillPriceData);
            if ($rebillPriceId === null) {
                throw new Exception(__('Unable to create prices on Rebill.'));
            }
            $rebillPrice->setData([
                'rebill_item_id'  => $rebillItem->getId(),
                'rebill_price_id' => $rebillPriceId,
                'details'         => json_encode([]),
                'details_hash'    => $hash,
                'order_id'        => $order->getId(),
                'order_item_id'   => null
            ]);
            $rebillPrice->save();
        }
        return $rebillPrice->getData('rebill_price_id');
    }

    protected function getRebillPriceIdForItem(Item $item)
    {
        $order = $item->getOrder();
        /** @var \Improntus\Rebill\Model\Item $rebillItem */
        $rebillItem = $this->itemFactory->create();
        $rebillItem->load($item->getProduct()->getSku(), 'product_sku');
        if (!$rebillItem->getId()) {
            $rebillItem->setData('product_sku', $item->getProduct()->getSku());
            $rebillItemId = $this->item->createItem([
                'name'        => $item->getProduct()->getName(),
                'description' => (string)$item->getProduct()->getData('short_description')
            ]);
            if ($rebillItemId === null) {
                throw new Exception(__('Unable to create items on Rebill.'));
            }
            $rebillItem->setData('rebill_item_id', $rebillItemId);
            $rebillItem->setData('product_description', $item->getProduct()->getSku() . ' - ' . $item->getProduct()->getName());
            $rebillItem->save();
        }
        $rebillItemId = $rebillItem->getData('rebill_item_id');
        if ($item->getParentItemId()) {
            $parentItem = $item->getParentItem();
            $rowTotal = $parentItem->getRowTotal()
                - $parentItem->getDiscountAmount()
                + $parentItem->getTaxAmount()
                + $parentItem->getDiscountTaxCompensationAmount();
            $itemQty = $parentItem->getQtyOrdered();
        } else {
            $rowTotal = $item->getRowTotal()
                - $item->getDiscountAmount()
                + $item->getTaxAmount()
                + $item->getDiscountTaxCompensationAmount();
            $itemQty = $item->getQtyOrdered();
        }
        $price = $rowTotal / $itemQty;
        $rebillDetails = $this->configHelper->getProductRebillSubscriptionDetails($item->getProduct());
        $frequency = $item->getProductOptionByCode('rebill_subscription');
        $cost = $frequency['initialCost'];
        /**
         * @TODO in future implementation it will be needed the gateway in $rebillDetails
         */
        $gateway = $this->configHelper->getGatewayId();
        $hash = hash('md5', json_encode($rebillDetails) . $price . $cost . $item->getProduct()->getSku() . $gateway);
        /** @var Price $rebillPrice */
        $rebillPrice = $this->priceFactory->create();
        $rebillPrice->load($hash, 'details_hash');
        if (!$rebillPrice->getId()) {
            $rebillPriceData = [
                'amount'      => (string)($price + $cost),
                'type'        => 'fixed',
                'repetitions' => 1,
                'currency'    => $this->configHelper->getCurrency(),
                'gatewayId'   => $gateway,
                'enabled'     => true
            ];
            if ($rebillDetails['enable_subscription']) {
                $rebillPriceData['frequency'] = [
                    'type'     => $frequency['frequencyType'] ?? 'months',
                    'quantity' => (int)$frequency['frequency'] ?? 1,
                ];
                $rebillPriceData['free_trial'] = [
                    'type'     => 'days',
                    'quantity' => $rebillDetails['free_trial_time_lapse'],
                ];
                $rebillPriceData['repetitions'] = $frequency['recurringPayments'];
            }
            $rebillPriceId = $this->item->createPriceForItem($rebillItemId, $rebillPriceData);
            if ($rebillPriceId === null) {
                throw new Exception(__('Unable to create prices on Rebill.'));
            }
            $rebillPrice->setData([
                'rebill_item_id'  => $rebillItem->getId(),
                'rebill_price_id' => $rebillPriceId,
                'details'         => json_encode($rebillDetails),
                'details_hash'    => $hash,
                'order_id'        => $order->getId(),
                'order_item_id'   => $item->getId()
            ]);
            $rebillPrice->save();
        }
        return $rebillPrice->getData('rebill_price_id');
    }
}
