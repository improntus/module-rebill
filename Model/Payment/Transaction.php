<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2022 Improntus (http://www.improntus.com/)
 * @package Improntus_Rebill
 */

namespace Improntus\Rebill\Model\Payment;

use Exception;
use Improntus\Rebill\Helper\Config;
use Improntus\Rebill\Model\ItemFactory;
use Improntus\Rebill\Model\Payment\Rebill as RebillPayment;
use Improntus\Rebill\Model\Price;
use Improntus\Rebill\Model\PriceFactory;
use Improntus\Rebill\Model\Rebill;
use Magento\Checkout\Model\Session;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Registry;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\QuoteRepository;
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
     * @var QuoteRepository
     */
    protected $quoteRepository;

    /**
     * @var array
     */
    private array $defaultFrequency = [
        'frequency'          => 0,
        'frequency_type'     => 'months',
        'recurring_payments' => 1,
    ];

    /**
     * @var array
     */
    private array $frequencyHashes = [];

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
     * @return array
     * @throws LocalizedException
     */
    public function prepareTransaction()
    {
        try {
            $order = $this->session->getLastRealOrder();
            if (!$order->getId() || $order->getPayment()->getMethod() !== RebillPayment::CODE) {
                throw new LocalizedException(__('Can\'t find any order to pay with rebill.'));
            }
            $this->registry->register('current_order', $order);
            $quote = $this->quoteRepository->get($order->getQuoteId());
            $items = $this->prepareItems($quote, $order);
            $this->prepareAdditionalItems($order, $quote, $items);
            $rebillDetails = $this->sendItems($order, $items);
            return [
                'order'          => $order,
                'rebill_details' => $rebillDetails,
            ];
        } catch (Exception $exception) {
            $this->session->restoreQuote();
            $this->configHelper->logError($exception->getMessage());
            throw new LocalizedException(__('There was an error creating the payment, please try againt.'));
        }
    }

    /**
     * @param Order $order
     * @param array $items
     * @return array
     * @throws Exception
     */
    private function sendItems(Order $order, array $items)
    {
        $defaultFrequencyHash = $this->createHashFromArray($this->defaultFrequency);
        $gateway = $this->configHelper->getGatewayId();
        $compiledItems = [];
        $frequenciesQty = count($items);
        foreach ($items as $hash => $_items) {
            if ($hash == $defaultFrequencyHash && $frequenciesQty == 1) {
                $compiledItems[] = [
                    'type'           => 'order',
                    'frequency_hash' => $defaultFrequencyHash,
                    'frequency'      => $this->defaultFrequency,
                    'sku'            => "order-{$order->getIncrementId()}",
                    'product_name'   => "Order #{$order->getIncrementId()}",
                    'price'          => $order->getGrandTotal(),
                    'quantity'       => 1,
                    'gateway'        => $gateway,
                    'currency'       => $this->configHelper->getCurrency(),
                ];
            } else {
                foreach ($_items as $item) {
                    $compiledItems[] = $item;
                }
            }
        }
        $result = [];
        foreach ($compiledItems as $item) {
            if (in_array($item['type'], ['shipment', 'additional']) && $item['price'] <= 0) {
                continue;
            }
            $rebillItem = $this->getRebillItem($item);
            $rebillPrice = $this->getRebillPrice($rebillItem, $item);
            $result[] = [
                'id'       => $rebillPrice->getData('rebill_price_id'),
                'quantity' => (int)$item['quantity'],
            ];
        }
        return $result;
    }

    /**
     * @param array $item
     * @return \Improntus\Rebill\Model\Item
     * @throws Exception
     */
    private function getRebillItem(array $item)
    {
        $rebillItem = $this->itemFactory->create();
        $rebillItem->load($item['sku'], 'product_sku');
        if (!$rebillItem->getId()) {
            $rebillItem->setData('product_sku', $item['sku']);
            $itemId = $this->item->createItem([
                'name'        => $item['product_name'],
                'description' => $item['product_name'],
            ]);
            if ($itemId === null) {
                throw new Exception(__('Unable to create items on Rebill.'));
            }
            $rebillItem->setData('rebill_item_id', $itemId);
            $rebillItem->setData('product_description', $item['product_name']);
            $rebillItem->save();
        }
        return $rebillItem;
    }

    /**
     * @param \Improntus\Rebill\Model\Item $rebillItem
     * @param array $item
     * @return Price
     * @throws Exception
     */
    private function getRebillPrice(
        \Improntus\Rebill\Model\Item $rebillItem,
        array                        $item,
    ) {
        $hash = $this->createHashFromArray($item);
        $rebillPrice = $this->priceFactory->create();
        $rebillPrice->load($hash, 'details_hash');
        if (!$rebillPrice->getId()) {
            $details = [
                'amount'      => (string)$item['price'],
                'type'        => 'fixed',
                'repetitions' => 1,
                'currency'    => $item['currency'],
                'gatewayId'   => $item['gateway'],
                'enabled'     => true,
            ];
            if ($item['frequency']['frequency'] > 0) {
                $details['frequency'] = [
                    'type'     => $item['frequency']['frequency_type'],
                    'quantity' => (int)$item['frequency']['frequency'],
                ];
                $details['repetitions'] = $item['frequency']['recurring_payments'];
            }
            $priceId = $this->item->createPriceForItem(
                $rebillItem->getData('rebill_item_id'),
                $details
            );
            if ($priceId === null) {
                throw new Exception(__('Unable to create prices on Rebill.'));
            }
            $rebillPrice->setData([
                'item_id'         => $rebillItem->getId(),
                'type'            => $item['type'],
                'rebill_item_id'  => $rebillItem->getData('rebill_item_id'),
                'rebill_price_id' => $priceId,
                'details'         => json_encode($details),
                'frequency_hash'  => $item['frequency_hash'],
                'details_hash'    => $hash,
            ]);
            $rebillPrice->save();
        }
        return $rebillPrice;
    }

    /**
     * @param Quote $quote
     * @param Order $order
     * @return array
     */
    private function prepareItems(Quote $quote, Order $order)
    {
        $items = $order->getAllVisibleItems();
        $gateway = $this->configHelper->getGatewayId();
        $preparedItems = [];
        $frequency = $this->defaultFrequency;
        /** @var Item $item */
        foreach ($items as $item) {
            $_item = $item;
            if ($item->getParentItemId()) {
                $_item = $item->getParentItem();
            }
            $discount = $_item->getDiscountAmount();
            $rowTotal = array_sum([
                $_item->getRowTotal(),
                $discount > 0 ? $discount * -1 : $discount,
                $_item->getTaxAmount(),
                $_item->getDiscountTaxCompensationAmount(),
            ]);
            $itemQty = $_item->getQtyOrdered();
            $price = $rowTotal / $itemQty;
            $frequencyOption = $quote->getItemById($item->getQuoteItemId())
                ->getOptionByCode('rebill_subscription');
            if ($frequencyOption) {
                $frequencyOption = json_decode($frequencyOption->getValue(), true);
                $frequency = [
                    'frequency'          => $frequencyOption['frequency'] ?? 0,
                    'frequency_type'     => $frequencyOption['frequencyType'] ?? 'months',
                    'recurring_payments' => $frequencyOption['recurringPayments'] ?? 1,
                ];
            }
            $frequencyHash = $this->createHashFromArray($frequency);
            $this->frequencyHashes[$frequencyHash] = $frequency;
            $preparedItems[$frequencyHash][] = [
                'type'           => 'product',
                'frequency_hash' => $frequencyHash,
                'frequency'      => $frequency,
                'sku'            => $item->getSku(),
                'product_name'   => $item->getName(),
                'price'          => $price,
                'quantity'       => $itemQty,
                'gateway'        => $gateway,
                'currency'       => $this->configHelper->getCurrency(),
            ];
        }
        return $preparedItems;
    }

    /**
     * @param Order $order
     * @param Quote $quote
     * @param array $items
     * @return void
     */
    private function prepareAdditionalItems(Order $order, Quote $quote, array &$items)
    {
        $defaultFrequencyHash = $this->createHashFromArray($this->defaultFrequency);
        $gateway = $this->configHelper->getGatewayId();
        $total = array_sum([
            $order->getGrandTotal(),
            $order->getShippingAmount() * -1,
            $order->getShippingTaxAmount() * -1,
            array_sum(array_map(function ($item) {
                /** @var Item $item */
                return $item->getRowTotal() * -1;
            }, $order->getAllVisibleItems())),
        ]);
        $additionalItem = [
            'type'           => 'additional',
            'frequency_hash' => $defaultFrequencyHash,
            'frequency'      => $this->defaultFrequency,
            'sku'            => "order-{$order->getIncrementId()}-additional-costs",
            'product_name'   => "Order #{$order->getIncrementId()} Additional Costs",
            'price'          => $total,
            'quantity'       => 1,
            'gateway'        => $gateway,
            'currency'       => $this->configHelper->getCurrency(),
        ];
        if (isset($items[$defaultFrequencyHash]) && count($items) == 1) {
            $additionalItem['price'] += $order->getShippingAmount() + $order->getShippingTaxAmount();
        } else {
            $shipmentPrice = ($order->getShippingAmount() + $order->getShippingTaxAmount()) / count($items);
            $_items = $items;
            foreach ($_items as $hash => $item) {
                $frequency = $this->frequencyHashes[$hash];
                $itemsSkus = array_map(function ($item) {
                    return $item['sku'];
                }, $item);
                $itemsNames = array_map(function ($item) {
                    return "({$item['product_name']})";
                }, $item);
                $sku = 'shipment-' . implode('-', $itemsSkus);
                $orderId = $order->getIncrementId();
                $name = "Order #{$orderId} Shipment " . implode(' ', $itemsNames);
                $items[$hash][] = [
                    'type'           => 'shipment',
                    'frequency_hash' => $hash,
                    'frequency'      => $frequency,
                    'sku'            => $sku,
                    'product_name'   => $name,
                    'price'          => $shipmentPrice,
                    'quantity'       => 1,
                    'gateway'        => $gateway,
                    'currency'       => $this->configHelper->getCurrency(),
                ];
            }
        }
        $items[$defaultFrequencyHash][] = $additionalItem;
    }

    /**
     * @param array $array
     * @return string
     */
    private function createHashFromArray(array $array)
    {
        return hash('md5', implode('-', array_map(function ($item) {
            return is_array($item) ? json_encode($item) : $item;
        }, $array)));
    }
}
