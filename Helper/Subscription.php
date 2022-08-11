<?php

namespace Improntus\Rebill\Helper;

use Exception;
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
        if ($rule->getData('rebill_apply_to_subscriptions')) {
            return true;
        }
        return false;
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
            $frequency['initialCost'] *= $item->getQtyOrdered();
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
            'enable_subscription'           => (bool)$product->getData('rebill_subscription_type'),
            'subscription_type'             => $product->getData('rebill_subscription_type'),
            'inherit_from_parent'           => (int)$product->getData('rebill_inherit_from_parent'),
            'individual_settings_in_simple' => (int)$product->getData('rebill_individual_settings_in_simple'),
            'free_trial_time_lapse'         => $product->getData('rebill_free_trial_time_lapse'),
            'frequency'                     => json_decode($product->getData('rebill_frequency') ?? '[]', true),
            'gateway_id'                    => $product->getData('rebill_gateway_id'),
        ];
    }

    public function isSubscriptionEnabled(Product $product)
    {
        return (bool)$product->getData('rebill_subscription_type');
    }

    public function setCurrentSubscription($id)
    {
        $this->registry->register('current_subscription_id', $id);
    }

    public function getCurrentSubscription()
    {
        return $this->registry->registry('current_subscription_id');
    }

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

    public function getFrequencyDescription(?Product $product = null, array $frequencyArray = [], $price = null)
    {
        try {
            $frequency = $frequencyArray['frequency'];
            $frequencyType = $frequencyArray['frequencyType'];
            $recurringPayments = $frequencyArray['recurringPayments'];
            $initialCost = $frequencyArray['initialCost'];
            if (!$price) {
                $price = $this->currencyHelper->currencyByStore($frequencyArray['price'], null, true, false);
                if ($product->getTypeId() == 'configurable' && !(int)$product->getData('rebill_individual_settings_in_simple')) {
                    $price = "+" . $price;
                }
            } else {
                $price = $this->currencyHelper->currencyByStore($price, null, true, false);
            }
            if ($frequency == 1) {
                if ($frequencyType == 'months') {
                    if ($recurringPayments == 0) {
                        if ($initialCost == 0) {
                            return __('monthly for %1', $price);
                        } else {
                            return __('monthly for %1 with a sign-up fee of %2', $price, $this->currencyHelper->currencyByStore($initialCost, null, true, false));
                        }
                    } else {
                        $recurringPaymentPeriod = __('months');
                        if ($recurringPayments == 12) {
                            $recurringPaymentPeriod = __('year');
                        } elseif ($recurringPayments > 12 && $recurringPayments % 12 == 0) {
                            $recurringPaymentPeriod = __('years');
                        }
                        if ($initialCost == 0) {
                            return __('monthly for %1 for %2 %3', $price, $recurringPayments, $recurringPaymentPeriod);
                        } else {
                            return __(
                                'monthly for %1 for %2 %3 with a sign-up fee of %4',
                                $price,
                                $recurringPayments,
                                $recurringPaymentPeriod,
                                $this->currencyHelper->currencyByStore($initialCost, null, true, false)
                            );
                        }
                    }
                } else {
                    if ($recurringPayments == 0) {
                        if ($initialCost == 0) {
                            return __('yearly for %1', $price);
                        } else {
                            return __('yearly for %1 with a sign-up fee of %2', $price, $this->currencyHelper->currencyByStore($initialCost, null, true, false));
                        }
                    } else {
                        $recurringPaymentPeriod = __('years');
                        if ($recurringPayments == 1) {
                            $recurringPaymentPeriod = __('year');
                        }
                        if ($initialCost == 0) {
                            return __('yearly for %1 for %2 %3', $price, $recurringPayments, $recurringPaymentPeriod);
                        } else {
                            return __(
                                'yearly for %1 for %2 %3 with a sign-up fee of %4',
                                $price,
                                $recurringPayments,
                                $recurringPaymentPeriod,
                                $this->currencyHelper->currencyByStore($initialCost, null, true, false)
                            );
                        }
                    }
                }
            } else {
                if ($frequencyType == 'months') {
                    if ($recurringPayments == 0) {
                        if ($initialCost == 0) {
                            return __('every %1 months for %2', $frequency, $price);
                        } else {
                            return __(
                                'every %1 months for %2 with a sign-up fee of %3',
                                $frequency,
                                $price,
                                $this->currencyHelper->currencyByStore($initialCost, null, true, false)
                            );
                        }
                    } else {
                        $recurringPaymentPeriod = __('months');
                        if ($recurringPayments * $frequency == 12) {
                            $recurringPaymentPeriod = __('year');
                        } elseif ($recurringPayments * $frequency > 12 && ($recurringPayments * $frequency) % 12 == 0) {
                            $recurringPaymentPeriod = __('years');
                        }
                        if ($initialCost == 0) {
                            return __(
                                'every %1 months for %2 for %3 %4',
                                $frequency,
                                $price,
                                $recurringPayments * $frequency,
                                $recurringPaymentPeriod
                            );
                        } else {
                            return __(
                                'every %1 months for %2 for %3 %4 with a sign-up fee of %5',
                                $frequency,
                                $price,
                                $recurringPayments * $frequency,
                                $recurringPaymentPeriod,
                                $this->currencyHelper->currencyByStore($initialCost, null, true, false)
                            );
                        }
                    }
                } else {
                    if ($recurringPayments == 0) {
                        if ($initialCost == 0) {
                            return __('every %1 years for %2', $frequency, $price);
                        } else {
                            return __(
                                'every %1 years for %2 with a sign-up fee of %3',
                                $frequency,
                                $price,
                                $this->currencyHelper->currencyByStore($initialCost, null, true, false)
                            );
                        }
                    } else {
                        $recurringPaymentPeriod = __('years');
                        if ($recurringPayments * $frequency == 1) {
                            $recurringPaymentPeriod = __('year');
                        }
                        if ($initialCost == 0) {
                            return __(
                                'every %1 years for %2 for %3 %4',
                                $frequency,
                                $price,
                                $recurringPayments * $frequency,
                                $recurringPaymentPeriod
                            );
                        } else {
                            return __(
                                'every %1 years for %2 for %3 %4 with a sign-up fee of %5',
                                $frequency,
                                $price,
                                $recurringPayments * $frequency,
                                $recurringPaymentPeriod,
                                $this->currencyHelper->currencyByStore($initialCost, null, true, false)
                            );
                        }
                    }
                }
            }
        } catch (Exception $exception) {
            return '';
        }
    }
}
