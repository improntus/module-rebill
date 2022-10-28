<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2022 Improntus (http://www.improntus.com/)
 * @package Improntus_Rebill
 */

namespace Improntus\Rebill\Model\Sales;

use Magento\Sales\Model\Order;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\QuoteRepository;
use Magento\Quote\Model\QuoteManagement;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Sales\Model\Reorder\Reorder as MagentoReorder;

class Reorder
{
    /**
     * @var MagentoReorder
     */
    protected $reorder;

    /**
     * @var QuoteRepository
     */
    protected $quoteRepository;

    /**
     * @var QuoteManagement
     */
    protected $cartManagement;

    /**
     * @param MagentoReorder $reorder
     * @param QuoteRepository $quoteRepository
     * @param CartManagementInterface $cartManagement
     */
    public function __construct(
        MagentoReorder          $reorder,
        QuoteRepository         $quoteRepository,
        CartManagementInterface $cartManagement
    )
    {
        $this->reorder = $reorder;
        $this->quoteRepository = $quoteRepository;
        $this->cartManagement = $cartManagement;
    }

    /**
     * @param Order $order
     * @param array $frequencies
     * @return OrderInterface|null
     * @throws CouldNotSaveException
     * @throws InputException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function execute(Order $order, array $frequencies = [])
    {
        $oldQuote = $this->quoteRepository->get($order->getQuoteId());
        $shippingAddress = $oldQuote->getShippingAddress();
        $payment = $oldQuote->getPayment();
        $result = $this->reorder->execute($order->getIncrementId(), $order->getStoreId());
        /** @var Quote $cart */
        $cart = $result->getCart();
        $cart->setStore($oldQuote->getStore());
        $cart->setShippingAddress($shippingAddress);
        if ($frequencies) {
            /** @var Quote\Item $item */
            foreach ($cart->getAllVisibleItems() as $item) {
                $frequencyOption = $item->getOptionByCode('rebill_subscription');
                if (!$frequencyOption) {
                    if ($children = $item->getChildren()) {
                        foreach ($children as $child) {
                            $cart->removeItem($child->getId());
                        }
                    }
                    $cart->removeItem($item->getId());
                    continue;
                }
                $frequencyOption = json_decode($frequencyOption->getValue(), true);
                $_frequencyQty = $frequencyOption['frequency'] ?? 0;
                $frequency = [
                    'frequency' => $_frequencyQty ?? 0,
                    'frequency_type' => $frequencyOption['frequencyType'] ?? 'months'
                ];
                if (isset($frequencyOption['recurringPayments']) && $frequencyOption['recurringPayments']) {
                    $frequency['recurring_payments'] = (int)$frequencyOption['recurringPayments'];
                }
                $frequencyHash = hash('md5', implode('-', $frequency));
                $remove = true;
                foreach ($frequencies as $sku => $_frequency) {
                    if ($_frequency == $frequencyHash && $item->getSku() == $sku) {
                        $remove = false;
                        break;
                    }
                }
                if ($remove) {
                    if ($children = $item->getChildren()) {
                        foreach ($children as $child) {
                            $cart->removeItem($child->getId());
                        }
                    }
                    $cart->removeItem($item->getId());
                    continue;
                }
                $productPrice = $item->getPrice();
                if ($product = $item->getProduct()) {
                    $productPrice = $frequencyOption['price'] + $product->getFinalPrice();
                }
                $item->setCustomPrice($productPrice);
                $item->setOriginalCustomPrice($productPrice);
                $item->getProduct()->setIsSuperMode(true);
            }
        }
        $shippingAddress = $shippingAddress
            ->setShippingMethod($order->getShippingMethod())
            ->setCollectShippingRates(true)
            ->collectShippingRates();
        $cart->setShippingAddress($shippingAddress);
        $cart->setBillingAddress($oldQuote->getBillingAddress());
        $cart->setPayment($payment);
        $cart->collectTotals();
        $this->quoteRepository->save($cart);
        $cart->getShippingAddress()
            ->setCollectShippingRates(true)
            ->collectShippingRates();
        $cart->getPayment()->setMethod($payment->getMethod());
        return $this->cartManagement->submit($cart);
    }
}
