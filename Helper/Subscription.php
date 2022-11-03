<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2022 Improntus (http://www.improntus.com/)
 * @package Improntus_Rebill
 */

namespace Improntus\Rebill\Helper;

use Exception;
use Magento\Framework\Phrase;
use Magento\Catalog\Model\Product;
use Magento\Quote\Model\Quote;
use Magento\Sales\Model\Order;
use Magento\SalesRule\Model\Rule;
use Magento\Framework\Exception\NoSuchEntityException;

class Subscription extends Data
{
    /**
     * @param Rule $rule
     * @return bool
     * @description return if cart rules apply to subscription products
     */
    public function cartRuleApplyToSubscriptionProducts(Rule $rule)
    {
        if ($rule->getData('rebill_apply_to_subscriptions') === 0) {
            return false;
        }
        return true;
    }

    /**
     * @param Quote $quote
     * @return array
     * @description return subscription details from quote items
     */
    public function getQuoteSubscriptionInformation(Quote $quote)
    {
        $subscriptionProducts = [];
        /** @var Quote\Item $item */
        foreach ($quote->getAllItems() as $item) {
            if ($item->getOptionByCode('rebill_subscription')) {
                $productData = json_decode($item->getOptionByCode('rebill_subscription')->getData()['value'], true);
                $productData['initialCost'] *= $item->getQty();
                $subscriptionProducts[] = $productData;
            }
        }
        return $subscriptionProducts;
    }

    /**
     * @param Quote $quote
     * @return bool
     * @description return if quote has subscription products
     */
    public function hasQuoteSubscriptionProducts(Quote $quote)
    {
        $hasSubscriptionProducts = false;
        /** @var Quote\Item $item */
        foreach ($quote->getAllItems() as $item) {
            if ($item->getOptionByCode('rebill_subscription') && !$item->getParentItemId()) {
                $hasSubscriptionProducts = true;
            }
        }
        return $hasSubscriptionProducts;
    }

    /**
     * @param Quote $quote
     * @return bool
     * @description return if quote has non subscription products
     */
    public function hasQuoteNoSubscriptionProducts(Quote $quote)
    {
        $hasNoSubscriptionProducts = false;
        /** @var Quote\Item $item */
        foreach ($quote->getAllItems() as $item) {
            if (!$item->getOptionByCode('rebill_subscription') && !$item->getParentItemId()) {
                $hasNoSubscriptionProducts = true;
            }
        }
        return $hasNoSubscriptionProducts;
    }

    /**
     * @param Order $order
     * @return bool
     * @description return if order has subscription products
     */
    public function hasOrderSubscriptionProducts(Order $order)
    {
        $hasSubscriptionProducts = false;
        /** @var Order\Item $item */
        foreach ($order->getAllItems() as $item) {
            $product = $this->productFactory->create()->load($item->getProduct()->getId());
            $productData = $this->getProductRebillSubscriptionDetails($product);
            if ($productData['enable_subscription']) {
                $hasSubscriptionProducts = true;
                break;
            }
        }
        return $hasSubscriptionProducts;
    }

    /**
     * @param Order $order
     * @return array
     * @throws NoSuchEntityException
     */
    public function getOrderSubscriptionInformation(Order $order)
    {
        $quote = $this->quoteRepository->get($order->getQuoteId());
        $subscriptionProducts = [];
        /** @var Order\Item $item */
        foreach ($order->getAllVisibleItems() as $item) {
            $quoteItem = $quote->getItemById($item->getQuoteItemId());
            $frequency = $quoteItem->getOptionByCode('rebill_subscription');
            $frequency = $frequency ? json_decode($frequency->getValue(), true) : [];
            if ($frequency) {
                $frequency['initialCost'] *= $item->getQtyOrdered();
            }
            $subscriptionProducts[] = $frequency;
        }
        return $subscriptionProducts;
    }

    /**
     * @param Product $product
     * @return array
     * @description return rebill attributes values from product
     */
    public function getProductRebillSubscriptionDetails(Product $product)
    {
        return [
            'enable_subscription' => (bool)$product->getData('rebill_subscription_type'),
            'subscription_type' => $product->getData('rebill_subscription_type'),
            'frequency' => json_decode($product->getData('rebill_frequency') ?? '[]', true),
        ];
    }

    /**
     * @param Product $product
     * @return bool
     */
    public function isSubscriptionEnabled(Product $product)
    {
        return (bool)$product->getData('rebill_subscription_type');
    }

    /**
     * @param $id
     * @return void
     */
    public function setCurrentSubscription($id)
    {
        if ($this->getCurrentSubscription()) {
            $this->registry->unregister('current_subscription_id');
        }
        $this->registry->register('current_subscription_id', $id);
    }

    /**
     * @return mixed|null
     */
    public function getCurrentSubscription()
    {
        return $this->registry->registry('current_subscription_id');
    }

    /**
     * @param Product $product
     * @return bool|int
     */
    public function isProductSubscriptionType(Product $product)
    {
        if ($product->getTypeId() == 'configurable') {
            return (bool)$product->getData('rebill_subscription_type');
        } elseif ($product->getTypeId() == 'virtual' || $product->getTypeId() == 'simple') {
            if ((int)$product->getData('rebill_inherit_from_parent')) {
                $parent = $product->getTypeInstance()->getParentIdsByChild($product->getId());
                if ($parent) {
                    $parentProduct = $this->productFactory->create();
                    $parentProduct->load($parent[0]);
                    return (bool)$parentProduct->getData('rebill_subscription_type');
                }
                return 0;
            } else {
                return (bool)$product->getData('rebill_subscription_type');
            }
        }
        return 0;
    }

    /**
     * @param Product|null $product
     * @param array $frequencyArray
     * @param float|null $price
     * @return Phrase|string
     */
    public function getFrequencyDescription(?Product $product = null, array $frequencyArray = [], float $price = null)
    {
        try {
            $frequency = (int)$frequencyArray['frequency'];
            $frequencyType = $frequencyArray['frequencyType'];
            $recurringPayments = (int)$frequencyArray['recurringPayments'];
            $initialCost = $frequencyArray['initialCost'];
            $price = $this->getFrequencyPrice($price ?? 0, $frequencyArray, $product);

            $description = $this->getFirstPartDesc($frequencyType, $frequency, $price);

            if ($recurringPayments == 0) {
                if ($initialCost == 0) {
                    return $description;
                } else {
                    return __(
                        '%1 with a sign-up fee of %2',
                        $description,
                        $this->currencyHelper->currencyByStore($initialCost, null, true, false)
                    );
                }
            } else {
                $recurringPaymentPeriod = $this->getRecurringPaymentPeriod(
                    $recurringPayments * $frequency,
                    $frequencyType
                );
                if ($initialCost == 0) {
                    return __(
                        '%1 with a maximum of %2 payments',
                        $description,
                        $recurringPayments - 1
                    );
                } else {
                    return __(
                        '%1 for %2 %3 with a sign-up fee of %4',
                        $description,
                        $recurringPayments * $frequency,
                        $recurringPaymentPeriod,
                        $this->currencyHelper->currencyByStore($initialCost, null, true, false)
                    );
                }
            }

        } catch (Exception $exception) {
            return '';
        }
    }

    /**
     * @param float|null $price
     * @return float|string
     */
    public function getFrequencyPriceFormat(float $price = null)
    {
        try {
            if (!$price) {
                $price = $this->currencyHelper->currencyByStore($price, null, true, false);
                $price = ($price > 0 ? '+' : ' ') . $price;
            } else {
                $price = $this->currencyHelper->currencyByStore($price, null, true, false);
            }
            return $price;
        } catch (Exception $exception) {
            return '';
        }
    }

    /**
     * @param float $price
     * @param array $frequencyArray
     * @param Product|null $product
     * @return float|string
     */
    private function getFrequencyPrice(float $price, array $frequencyArray, ?Product $product = null)
    {
        if (!$price) {
            $price = $this->currencyHelper->currencyByStore($frequencyArray['price'], null, false, false);
            $price += $product ? $product->getFinalPrice() : 0;
            $price = $this->currencyHelper->currencyByStore($price, null, true, false);
            $price = ($frequencyArray['price'] > 0 && !$product ? '+' : ' ') . $price;
        } else {
            $price = $this->currencyHelper->currencyByStore($price, null, true, false);
        }
        return $price;
    }

    /**
     * @param int $recurringPayments
     * @param string $frequencyType
     * @return Phrase
     */
    private function getRecurringPaymentPeriod(int $recurringPayments, string $frequencyType)
    {
        if ($frequencyType == 'months') {
            $recurringPaymentPeriod = __('months');
            if ($recurringPayments == 12) {
                $recurringPaymentPeriod = __('year');
            } elseif ($recurringPayments > 12 && $recurringPayments % 12 == 0) {
                $recurringPaymentPeriod = __('years');
            }
        } else {
            $recurringPaymentPeriod = __($recurringPayments == 1 ? 'year' : 'years');
        }

        return $recurringPaymentPeriod;
    }

    /**
     * @param string $frequencyType
     * @param int $frequency
     * @param string $price
     * @return Phrase
     */
    private function getFirstPartDesc(string $frequencyType, int $frequency, string $price)
    {
        if ($frequency == 1) {
            $period = __($frequencyType == 'months' ? 'monthly' : 'yearly');
            $description = __('%1 for %2', $period, $price);
        } else {
            $period = __($frequencyType == 'months' ? 'months' : 'years');
            $description = __('every %1 %2 for %3', $frequency, $period, $price);
        }
        return $description;
    }
}
