<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2022 Improntus (http://www.improntus.com/)
 * @package Improntus_Rebill
 */

namespace Improntus\Rebill\Model\Payment;

use Exception;
use Improntus\Rebill\Api\Price\DataInterface;
use Improntus\Rebill\Helper\Config;
use Improntus\Rebill\Model\Entity\Price\Repository;
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
     * @var Registry
     */
    protected $registry;

    /**
     * @var QuoteRepository
     */
    protected $quoteRepository;

    /**
     * @var Repository
     */
    protected $priceRepository;

    /**
     * @var array
     */
    private array $defaultFrequency = [
        'frequency' => 0,
        'frequency_type' => 'months',
        'recurring_payments' => 1,
    ];

    /**
     * @var array
     */
    private array $frequencyHashes = [];

    /**
     * @param Config $configHelper
     * @param Session $session
     * @param Repository $priceRepository
     * @param Registry $registry
     * @param QuoteRepository $quoteRepository
     */
    public function __construct(
        Config          $configHelper,
        Session         $session,
        Repository      $priceRepository,
        Registry        $registry,
        QuoteRepository $quoteRepository
    ) {
        $this->priceRepository = $priceRepository;
        $this->quoteRepository = $quoteRepository;
        $this->registry = $registry;
        $this->configHelper = $configHelper;
        $this->session = $session;
    }

    /**
     * @return string
     * phpcs:disable
     */
    public static function getDefaultFrequencyHash()
    {
        return self::createHashFromArray([
            'frequency' => 0,
            'frequency_type' => 'months',
            'recurring_payments' => 1,
        ]);
    }

    /**
     * @return array
     * @throws LocalizedException
     */
    public function prepareTransaction()
    {
        $order = $this->session->getLastRealOrder();
        try {
            if (!$order->getId() || $order->getPayment()->getMethod() !== 'improntus_rebill') {
                throw new LocalizedException(__('Can\'t find any order to pay with rebill.'));
            }
            $this->registry->register('current_order', $order);
            $quote = $this->quoteRepository->get($order->getQuoteId());
            $items = $this->prepareItems($quote, $order);
            $this->prepareAdditionalItems($order, $quote, $items);
            $rebillDetails = $this->sendItems($order, $items);
            return [
                'order' => $order,
                'rebill_details' => $rebillDetails,
            ];
        } catch (Exception $exception) {
            if ($order->getId()) {
                $order->cancel();
            }
            $this->session->restoreQuote();
            $this->configHelper->logError($exception->getMessage());
            throw new LocalizedException(__('There was an error creating the payment, please try again.'));
        }
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
        /** @var Item $item */
        foreach ($items as $item) {
            $_item = $item;
            $frequency = $this->defaultFrequency;
            if ($item->getParentItemId()) {
                $_item = $item->getParentItem();
            }
            $discount = $_item->getDiscountAmount();
            $rowTotal = $this->configHelper->arraySumDecimalRight([
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
                $_frequencyQty = $frequencyOption['frequency'] ?? 0;
                $frequency = [
                    'frequency' => $_frequencyQty ?? 0,
                    'frequency_type' => $frequencyOption['frequencyType'] ?? 'months',
                ];
                if (isset($frequencyOption['recurringPayments']) && $frequencyOption['recurringPayments'] > 0) {
                    $frequency['recurring_payments'] = (int)$frequencyOption['recurringPayments'];
                }
            }
            $frequencyHash = self::createHashFromArray($frequency);
            $this->frequencyHashes[$frequencyHash] = $frequency;
            $rebillItem = [
                'type' => 'product',
                'frequency_hash' => $frequencyHash,
                'frequency' => $frequency,
                'sku' => $item->getSku(),
                'product_name' => $item->getName(),
                'price' => $price,
                'quantity' => $itemQty,
                'gateway' => $gateway,
                'currency' => $this->configHelper->getCurrency(),
            ];
            $preparedItems = $this->filterDuplicates($preparedItems, $frequencyHash, $rebillItem);
        }
        return $preparedItems;
    }

    /**
     * @param array $array
     * @return string
     * phpcs:disable
     */
    public static function createHashFromArray(array $array)
    {
        return hash('md5', implode('-', array_map(function ($item) {
            return is_array($item) ? json_encode($item) : $item;
        }, $array)));
    }

    /**
     * @param Order $order
     * @param Quote $quote
     * @param array $items
     * @return void
     */
    private function prepareAdditionalItems(Order $order, Quote $quote, array &$items)
    {
        $defaultFrequencyHash = self::createHashFromArray($this->defaultFrequency);
        $gateway = $this->configHelper->getGatewayId();
        $itemsTotals = [];
        foreach ($order->getAllVisibleItems() as $item) {
            $discount = $item->getDiscountAmount();
            $rowTotal = $this->configHelper->arraySumDecimalRight([
                $item->getRowTotal(),
                $discount > 0 ? $discount * -1 : $discount,
                $item->getTaxAmount(),
                $item->getDiscountTaxCompensationAmount(),
            ]);
            $itemsTotals[] = $rowTotal * -1;
        }
        $total = $this->configHelper->arraySumDecimalRight([
            $order->getGrandTotal(),
            $order->getShippingAmount() * -1,
            $order->getShippingTaxAmount() * -1,
            $order->getShippingDiscountAmount(),
            $order->getShippingDiscountTaxCompensationAmount(),
            array_sum($itemsTotals),
        ]);
        $additionalItem = [
            'type' => 'additional',
            'frequency_hash' => $defaultFrequencyHash,
            'frequency' => $this->defaultFrequency,
            'sku' => "order-{$order->getIncrementId()}-additional-costs",
            'product_name' => "Order #{$order->getIncrementId()} Additional Costs",
            'price' => $total,
            'quantity' => 1,
            'gateway' => $gateway,
            'currency' => $this->configHelper->getCurrency(),
        ];
        if (isset($items[$defaultFrequencyHash]) && count($items) == 1) {
            $additionalItem['price'] += $order->getShippingAmount() + $order->getShippingTaxAmount();
        } else {
            $itemsQty = count($items);
            if (isset($items[$defaultFrequencyHash])) {
                $itemsQty--;
            }
            $shipmentPrice = $this->configHelper->arraySumDecimalRight([
                    $order->getShippingAmount(),
                    $order->getShippingTaxAmount(),
                    -$order->getShippingDiscountAmount(),
                    -$order->getShippingDiscountTaxCompensationAmount(),
                ]) / $itemsQty;
            $_items = $items;
            foreach ($_items as $hash => $item) {
                if ($hash == $defaultFrequencyHash) {
                    continue;
                }
                $frequency = $this->frequencyHashes[$hash];
                $itemsSkus = array_map(function ($item) {
                    return $item['sku'];
                }, $item);
                $itemsNames = array_map(function ($item) {
                    return "({$item['product_name']})";
                }, $item);
                $orderId = $order->getIncrementId();
                $sku = "shipment-{$orderId}";
                $name = $this->configHelper->getShortDescription("Order #{$orderId} Shipment " . implode(' ', $itemsNames), 250);
                $items[$hash][] = [
                    'type' => 'shipment',
                    'frequency_hash' => $hash,
                    'frequency' => $frequency,
                    'sku' => $sku,
                    'product_name' => $this->configHelper->getShortDescription($sku, 50),
                    'price_name' => $name,
                    'price' => $shipmentPrice,
                    'quantity' => 1,
                    'gateway' => $gateway,
                    'currency' => $this->configHelper->getCurrency(),
                ];
            }
        }
        $items[$defaultFrequencyHash][] = $additionalItem;
    }

    /**
     * @param Order $order
     * @param array $items
     * @return array
     * @throws Exception
     */
    private function sendItems(Order $order, array $items)
    {
        $defaultFrequencyHash = self::createHashFromArray($this->defaultFrequency);
        $gateway = $this->configHelper->getGatewayId();
        $compiledItems = [];
        foreach ($items as $hash => $_items) {
            if ($hash == $defaultFrequencyHash) {
                foreach ($_items as $item) {
                    $compiledItems[] = [
                        'type' => $item['type'],
                        'frequency_hash' => $defaultFrequencyHash,
                        'frequency' => $this->defaultFrequency,
                        'sku' => $item['sku'],
                        'product_name' => $item['price_name'] ?? $item['product_name'],
                        'price' => $item['price'],
                        'quantity' => $item['quantity'],
                        'gateway' => $gateway,
                        'currency' => $this->configHelper->getCurrency(),
                    ];
                }
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
            $rebillPrice = $this->getRebillPrice($item);
            $result[] = [
                'id' => $rebillPrice->getRebillPriceId(),
                'quantity' => (int)$item['quantity'],
            ];
        }
        return $result;
    }//phpcs:enable

    /**
     * @param array $item
     * @return DataInterface
     * @throws Exception
     */
    private function getRebillPrice(array $item)
    {
        $hash = self::createHashFromArray(self::filterByKeys($item));
        $rebillPrice = $this->priceRepository->getByHash($hash);
        if (!$rebillPrice->getId()) {
            $details = [
                'amount' => (string)$item['price'],
                'type' => 'fixed',
                'repetitions' => 1,
                'currency' => $item['currency'],
                'gatewayId' => $item['gateway'],
                'description' => $item['price_name'] ?? $item['product_name'],
                'enabled' => true,
            ];
            if ($item['frequency']['frequency'] > 0) {
                $details['frequency'] = [
                    'type' => $item['frequency']['frequency_type'],
                    'quantity' => (int)$item['frequency']['frequency'],
                ];
                $details['repetitions'] = $item['frequency']['recurring_payments'] ?? null;
            }
            $rebillPrice->setType($item['type']);
            $rebillPrice->setDetails($item);
            $rebillPrice->setRebillDetails($details);
            $rebillPrice->setDetailsHash($hash);
            $rebillPrice->setFrequencyHash($item['frequency_hash']);
            $this->priceRepository->save($rebillPrice);
        }
        if ($rebillPrice->getRebillPriceId() === null) {
            throw new Exception(__('Unable to create prices on Rebill.'));
        }
        return $rebillPrice;
    }//phpcs:enable

    /**
     * @param array $item
     * @return array
     */
    private function filterByKeys(array $item): array
    {
        return array_filter(
            $item,
            function ($key) {
                return $key != 'quantity';
            },
            ARRAY_FILTER_USE_KEY
        );
    }

    /**
     * @param $preparedItems
     * @param $frequencyHash
     * @param $rebillItem
     * @return mixed
     */
    private function filterDuplicates($preparedItems, $frequencyHash, $rebillItem)
    {
        if (array_key_exists($frequencyHash, $preparedItems)) {
            $existsItem = false;
            foreach ($preparedItems[$frequencyHash] as &$item) {
                if ($item['sku'] == $rebillItem['sku'] && $item["price"] == $rebillItem['price']) {
                    $item["quantity"] += $rebillItem["quantity"];
                    $existsItem = true;
                    break;
                }
            }

            if (!$existsItem) {
                $preparedItems[$frequencyHash][] = $rebillItem;
            }
        } else {
            $preparedItems[$frequencyHash][] = $rebillItem;
        }
        return $preparedItems;
    }
}
