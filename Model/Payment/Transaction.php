<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2022 Improntus (http://www.improntus.com/)
 * @package Improntus_Rebill
 */

namespace Improntus\Rebill\Model\Payment;

use Exception;
use Magento\Quote\Model\Quote;
use Improntus\Rebill\Helper\Config;
use Improntus\Rebill\Model\ItemFactory;
use Improntus\Rebill\Model\Price;
use Improntus\Rebill\Model\PriceFactory;
use Magento\Checkout\Model\Session;
use Improntus\Rebill\Model\Rebill;
use Magento\Framework\Registry;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Item;
use Magento\Quote\Model\QuoteRepository;

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
     * @var QuoteRepository
     */
    protected $quoteRepository;

    /**
     * @param Config $configHelper
     * @param Session $session
     * @param Rebill\Item $item
     * @param ItemFactory $itemFactory
     * @param PriceFactory $priceFactory
     * @param Registry $registry
     * @param QuoteRepository $quoteRepository
     */
    public function __construct(
        Config          $configHelper,
        Session         $session,
        Rebill\Item     $item,
        ItemFactory     $itemFactory,
        PriceFactory    $priceFactory,
        Registry        $registry,
        QuoteRepository $quoteRepository
    ) {
        $this->quoteRepository = $quoteRepository;
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
                $this->session->restoreQuote();
                throw new Exception(__('Can\'t find any order to pay with rebill.'));
            }
            $this->registry->register('prepared_order', $order);
            $this->registry->register('current_order', $order);
            $quote = $this->quoteRepository->get($order->getQuoteId());
            $items = $order->getAllVisibleItems();
            $prices = [];
            /** @var Item $item */
            foreach ($items as $item) {
                $rebillPriceId = $this->getRebillPriceIdForItem($item, $quote);
                $prices[] = [
                    'id'       => $rebillPriceId,
                    'quantity' => (int)$item->getQtyOrdered(),
                ];
            }
            if ($id = $this->getRebillPriceIdForShipping($order)) {
                $prices[] = [
                    'id'       => $id,
                    'quantity' => 1,
                ];
            }
            if ($id = $this->getRebillPriceIdForAdditionalCosts($order, $quote)) {
                $prices[] = [
                    'id'       => $id,
                    'quantity' => 1,
                ];
            }
            $this->registry->register('rebill_prices', $prices);
        } catch (Exception $exception) {
            $this->session->restoreQuote();
            $this->configHelper->logError($exception->getMessage());
            throw new \Exception('There was an error creating the payment, please try againt.');
        }
        return true;
    }

    /**
     * @param Order $order
     * @param Quote $quote
     * @return array|mixed|null
     * @throws Exception
     */
    protected function getRebillPriceIdForAdditionalCosts(Order $order, Quote $quote)
    {
        $total = $order->getGrandTotal()
            - $order->getShippingAmount()
            - $order->getShippingTaxAmount();
        /** @var Item $item */
        foreach ($order->getAllVisibleItems() as $item) {
            $total -= $item->getRowTotal();
            $quoteItem = $quote->getItemById($item->getQuoteItemId());
            $rebillSubscription = $quoteItem->getOptionByCode('rebill_subscription');
            $rebillSubscription = $rebillSubscription ? json_decode($rebillSubscription->getValue(), true) : [];
            $total += ($rebillSubscription['initialCost'] ?? 0) * $item->getQtyOrdered();
        }
        $total += $order->getTaxAmount();
        if ($total <= 0) {
            return null;
        }
        $rebillItem = $this->getRebillItem(
            "order_{$order->getIncrementId()}_additional",
            "Order #{$order->getIncrementId()} Additional",
            "Order #{$order->getIncrementId()} Additional"
        );
        $gateway = $this->configHelper->getGatewayId();
        $hash = hash('md5', $total . $order->getShippingDescription() . $gateway);
        $rebillPrice = $this->getRebillPrice($rebillItem, $hash, $gateway, $total);
        return $rebillPrice->getData('rebill_price_id');
    }

    /**
     * @param Order $order
     * @return array|mixed|null
     * @throws Exception
     */
    protected function getRebillPriceIdForShipping(Order $order)
    {
        if (!$order->getShippingAmount() == 0) {
            return null;
        }
        $rebillItem = $this->getRebillItem(
            $order->getShippingMethod(),
            $order->getShippingDescription(),
            $order->getShippingDescription()
        );
        $rowTotal = $order->getShippingAmount()
            - $order->getShippingDiscountAmount()
            + $order->getShippingTaxAmount()
            + $order->getShippingDiscountTaxCompensationAmount();
        $itemQty = 1;
        $price = $rowTotal / $itemQty;
        $gateway = $this->configHelper->getGatewayId();
        $hash = hash('md5', $price . $order->getShippingDescription() . $gateway);
        $rebillPrice = $this->getRebillPrice($rebillItem, $hash, $gateway, $price);
        return $rebillPrice->getData('rebill_price_id');
    }

    /**
     * @param Item $item
     * @param Quote $quote
     * @return array|mixed|null
     * @throws Exception
     */
    public function getRebillPriceIdForItem(Item $item, Quote $quote)
    {
        $rebillItem = $this->getRebillItem(
            $item->getProduct()->getSku(),
            $item->getProduct()->getName(),
            $item->getProduct()->getData('short_description')
        );
        $_item = $item;
        if ($item->getParentItemId()) {
            $_item = $item->getParentItem();
        }
        $rowTotal = $_item->getRowTotal()
            - $_item->getDiscountAmount()
            + $_item->getTaxAmount()
            + $_item->getDiscountTaxCompensationAmount();
        $itemQty = $_item->getQtyOrdered();
        $price = $rowTotal / $itemQty;
        $rebillDetails = $this->configHelper->getProductRebillSubscriptionDetails($item->getProduct());
        $quoteItem = $quote->getItemById($item->getQuoteItemId());
        $frequency = $quoteItem->getOptionByCode('rebill_subscription');
        $frequency = $frequency ? json_decode($frequency->getValue(), true) : [];
        $cost = $frequency['initialCost'] ?? 0;
        /**
         * @TODO in future implementation it will be needed the gateway in $rebillDetails
         */
        $gateway = $this->configHelper->getGatewayId();
        $hash = hash('md5', json_encode($frequency) . $price . $cost . $item->getProduct()->getSku() . $gateway);
        $rebillPrice = $this->getRebillPrice($rebillItem, $hash, $gateway, $price + $cost, $rebillDetails, $frequency);
        return $rebillPrice->getData('rebill_price_id');
    }

    /**
     * @param \Improntus\Rebill\Model\Item $item
     * @param string $hash
     * @param string $gateway
     * @param float $price
     * @param float $cost
     * @param array $rebillDetails
     * @param array $frequency
     * @return Price
     * @throws Exception
     */
    protected function getRebillPrice(
        \Improntus\Rebill\Model\Item $item,
        string $hash,
        string $gateway,
        float $price,
        array $rebillDetails = [],
        array $frequency = []
    ) {
        $rebillPrice = $this->priceFactory->create();
        $rebillPrice->load($hash, 'details_hash');
        if (!$rebillPrice->getId()) {
            $rebillPriceData = [
                'amount'      => (string)$price,
                'type'        => 'fixed',
                'repetitions' => 1,
                'currency'    => $this->configHelper->getCurrency(),
                'gatewayId'   => $gateway,
                'enabled'     => true,
            ];
            if ($rebillDetails['enable_subscription'] && $frequency) {
                $rebillPriceData['frequency'] = [
                    'type'     => $frequency['frequencyType'] ?? 'months',
                    'quantity' => (int)($frequency['frequency'] ?: 1),
                ];
                $rebillPriceData['free_trial'] = [
                    'type'     => 'days',
                    'quantity' => $rebillDetails['free_trial_time_lapse'] ?? 0,
                ];
                $rebillPriceData['repetitions'] = $frequency['recurringPayments'] ?: 0;
            }
            $rebillPriceId = $this->item->createPriceForItem($item->getData('rebill_item_id'), $rebillPriceData);
            if ($rebillPriceId === null) {
                throw new Exception(__('Unable to create prices on Rebill.'));
            }
            $rebillPrice->setData($this->formatPriceData($item->getId(), $rebillPriceId, $rebillDetails, $hash));
            $rebillPrice->save();
        }
        return $rebillPrice;
    }

    /**
     * @param string $itemId
     * @param string $priceId
     * @param array $details
     * @param string $hash
     * @return array
     */
    protected function formatPriceData(string $itemId, string $priceId, array $details, string $hash)
    {
        return [
            'rebill_item_id'  => $itemId,
            'rebill_price_id' => $priceId,
            'details'         => json_encode($details),
            'details_hash'    => $hash,
        ];
    }

    /**
     * @param string $sku
     * @param string $name
     * @param string $description
     * @return \Improntus\Rebill\Model\Item
     * @throws Exception
     */
    protected function getRebillItem(string $sku, string $name, string $description = '')
    {
        $rebillItem = $this->itemFactory->create();
        $rebillItem->load($sku, 'product_sku');
        if (!$rebillItem->getId()) {
            $rebillItem->setData('product_sku', $sku);
            $rebillItemId = $this->item->createItem(
                $this->formatItemData(
                    $name,
                    $description
                )
            );
            if ($rebillItemId === null) {
                throw new Exception(__('Unable to create items on Rebill.'));
            }
            $rebillItem->setData('rebill_item_id', $rebillItemId);
            $rebillItem->setData('product_description', "$sku - $name");
            $rebillItem->save();
        }
        return $rebillItem;
    }

    /**
     * @param string $name
     * @param string $description
     * @return string[]
     */
    protected function formatItemData(string $name, string $description)
    {
        return [
            'name'        => $name,
            'description' => $description,
        ];
    }
}
