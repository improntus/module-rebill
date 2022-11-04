<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2022 Improntus (http://www.improntus.com/)
 * @package Improntus_Rebill
 */

namespace Improntus\Rebill\Model\Sales;

use Exception;
use Improntus\Rebill\Helper\Config;
use Improntus\Rebill\Model\Payment\Transaction;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ProductFactory;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Model\AbstractExtensibleModel;
use Magento\Framework\Phrase;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Model\Cart\CustomerCartResolver;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\QuoteManagement;
use Magento\Quote\Model\QuoteRepository;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Item;
use Magento\Sales\Model\Reorder\OrderInfoBuyRequestGetter;
use Magento\Sales\Model\Reorder\Reorder as MagentoReorder;
use Zend_Mail;
use Zend_Mail_Exception;

class Reorder
{
    private const SUBSTITUTE_SHIPPING_METHOD = 'flatrate_flatrate';

    /**
     * @var MagentoReorder
     */
    private $reorder;

    /**
     * @var QuoteRepository
     */
    private $quoteRepository;

    /**
     * @var QuoteManagement
     */
    private $cartManagement;

    /**
     * @var ProductFactory
     */
    private $productFactory;

    /**
     * @var OrderInfoBuyRequestGetter
     */
    private $orderInfoBuyRequestGetter;

    /**
     * @var CustomerCartResolver
     */
    private $customerCartProvider;

    /**
     * @var Config
     */
    private $helperConfig;

    /**
     * @var array
     */
    private $errors = [];

    /**
     * @param MagentoReorder $reorder
     * @param QuoteRepository $quoteRepository
     * @param CartManagementInterface $cartManagement
     * @param ProductFactory $productFactory
     * @param CustomerCartResolver $customerCartProvider
     * @param OrderInfoBuyRequestGetter $orderInfoBuyRequestGetter
     * @param Config $helperConfig
     */
    public function __construct(
        MagentoReorder            $reorder,
        QuoteRepository           $quoteRepository,
        CartManagementInterface   $cartManagement,
        ProductFactory            $productFactory,
        CustomerCartResolver      $customerCartProvider,
        OrderInfoBuyRequestGetter $orderInfoBuyRequestGetter,
        Config                    $helperConfig
    ) {
        $this->helperConfig = $helperConfig;
        $this->customerCartProvider = $customerCartProvider;
        $this->orderInfoBuyRequestGetter = $orderInfoBuyRequestGetter;
        $this->productFactory = $productFactory;
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
//    public function _execute(Order $order, array $frequencies = [])
//    {
//        $oldQuote = $this->quoteRepository->get($order->getQuoteId());
//        $shippingAddress = $oldQuote->getShippingAddress();
//        $payment = $oldQuote->getPayment();
//        $result = $this->reorder->execute($order->getIncrementId(), $order->getStoreId());
//        /** @var Quote $cart */
//        $cart = $result->getCart();
//        $cart->setStore($oldQuote->getStore());
//        $cart->setShippingAddress($shippingAddress);
//        if ($frequencies) {
//            /** @var Quote\Item $item */
//            foreach ($cart->getAllVisibleItems() as $item) {
//                $frequencyOption = $item->getOptionByCode('rebill_subscription');
//                if (!$frequencyOption) {
//                    if ($children = $item->getChildren()) {
//                        foreach ($children as $child) {
//                            $cart->removeItem($child->getId());
//                        }
//                    }
//                    $cart->removeItem($item->getId());
//                    continue;
//                }
//                $frequencyOption = json_decode($frequencyOption->getValue(), true);
//                $_frequencyQty = $frequencyOption['frequency'] ?? 0;
//                $frequency = [
//                    'frequency'      => $_frequencyQty ?? 0,
//                    'frequency_type' => $frequencyOption['frequencyType'] ?? 'months',
//                ];
//                if (isset($frequencyOption['recurringPayments']) && $frequencyOption['recurringPayments']) {
//                    $frequency['recurring_payments'] = (int)$frequencyOption['recurringPayments'];
//                }
//                $frequencyHash = hash('md5', implode('-', $frequency));
//                $remove = true;
//                foreach ($frequencies as $sku => $_frequency) {
//                    if ($_frequency == $frequencyHash && $item->getSku() == $sku) {
//                        $remove = false;
//                        break;
//                    }
//                }
//                if ($remove) {
//                    if ($children = $item->getChildren()) {
//                        foreach ($children as $child) {
//                            $cart->removeItem($child->getId());
//                        }
//                    }
//                    $cart->removeItem($item->getId());
//                    continue;
//                }
//                $productPrice = $item->getPrice();
//                if ($product = $item->getProduct()) {
//                    $productPrice = $frequencyOption['price'] + $product->getFinalPrice();
//                }
//                $item->setCustomPrice($productPrice);
//                $item->setOriginalCustomPrice($productPrice);
//                $item->getProduct()->setIsSuperMode(true);
//            }
//        }
//        if (count($frequencies) != $cart->getItemsCount()) {
//            return null;
//        }
//        $shippingAddress = $shippingAddress
//            ->setShippingMethod($order->getShippingMethod())
//            ->setCollectShippingRates(true)
//            ->collectShippingRates();
//        $cart->setShippingAddress($shippingAddress);
//        $cart->setBillingAddress($oldQuote->getBillingAddress());
//        $cart->setPayment($payment);
//        $cart->collectTotals();
//        $this->quoteRepository->save($cart);
//        $cart->getShippingAddress()
//            ->setCollectShippingRates(true)
//            ->collectShippingRates();
//        // if shipping method doesn't apply anymore, then flatrate is selected
//        // and the shipping amount will be equal to the latest order
//        if (!$cart->getShippingAddress()->getShippingMethod()) {
//            $cart->getShippingAddress()->setShippingMethod('flatrate_flatrate');
//            $cart->getShippingAddress()->setShippingAmount($order->getShippingAmount());
//            $cart->getShippingAddress()->setShippingTaxAmount($order->getShippingTaxAmount());
//            $cart->getShippingAddress()->setShippingDiscountAmount($order->getShippingDiscountAmount());
//            $cart->getShippingAddress()->setBaseShippingAmount($order->getBaseShippingAmount());
//            $cart->getShippingAddress()->setBaseShippingTaxAmount($order->getBaseShippingTaxAmount());
//            $cart->getShippingAddress()->setBaseShippingDiscountAmount($order->getBaseShippingDiscountAmount());
//        }
//        $cart->getPayment()->setMethod($payment->getMethod());
//        return $this->cartManagement->submit($cart);
//    }

    /**
     * @param Order $_order
     * @param array $frequencies
     * @param string $subscription
     * @param int $queueId
     * @return AbstractExtensibleModel|OrderInterface|object|null
     * @throws CouldNotSaveException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function execute(Order $_order, array $frequencies, string $subscription, int $queueId)
    {
        $newCartData = $this->buildNewCartData($_order, $frequencies);
        if (!$newCartData['items']) {
            return null;
        }
        $newCart = $this->customerCartProvider->resolve($_order->getCustomerId());
        foreach ($newCartData['items'] as $item) {
            $this->addItemToCart($item['order_item'], $newCart, $item['product'], $item['frequency']);
        }
        $newCart->setShippingAddress($newCartData['shipping_address']);
        $newCart->setBillingAddress($newCartData['billing_address']);
        $newCart->setStore($_order->getStore());
        $shippingAddress = $newCart->getShippingAddress();
        try {
            $shippingAddress = $shippingAddress
                ->setShippingMethod($newCartData['shipping_method'])
                ->setCollectShippingRates(true)
                ->collectShippingRates();
        } catch (Exception $exception) {
            $shippingMethodError = $exception->getMessage();
        }
        $newCart->setPayment($newCartData['payment']);
        $newCart->collectTotals();
        if (!$shippingAddress->getShippingMethod()) {
            $this->addError(__(
                'The selected shipping method cannot be selected. Reason: %',
                $shippingMethodError
                ?? "The shipping method '{$newCartData['shipping_method']}' doesn\'t apply to the current cart"
            ));
            $shippingAddress->setShippingMethod(self::SUBSTITUTE_SHIPPING_METHOD);
            $shippingAddress->setShippingAmount($newCartData['shipping_costs']['shipping_amount']);
            $shippingAddress->setShippingTaxAmount($newCartData['shipping_costs']['shipping_tax_amount']);
            $shippingAddress->setShippingDiscountAmount($newCartData['shipping_costs']['shipping_discount_amount']);
            $shippingAddress->setShippingDiscountTaxCompensationAmount(
                $newCartData['shipping_costs']['shipping_discount_tax_compensation_amount']
            );
            $shippingAddress->setBaseShippingAmount($newCartData['shipping_costs']['shipping_amount']);
            $shippingAddress->setBaseShippingTaxAmount($newCartData['shipping_costs']['shipping_tax_amount']);
            $shippingAddress->setBaseShippingDiscountAmount($newCartData['shipping_costs']['shipping_discount_amount']);
        }
        $newCart->getPayment()->setMethod($newCartData['payment_method']);
        $this->quoteRepository->save($newCart);
        try {
            $order = $this->cartManagement->submit($newCart);
        } catch (Exception $exception) {
            $this->addError($exception->getMessage());
        }
        if ($this->errors) {
            try {
                $this->addError(__('Order #%1', $_order->getIncrementId()));
                $this->addError(__('Subscription: %1', $subscription));
                $this->addError(__('Queue Id: %1', $queueId));
                $to = [$this->helperConfig->getFailedReorderEmail()];
                $email = new Zend_Mail();
                $email->setSubject("Subscription Reorder Failed");
                $email->setBodyText(json_encode($this->errors, JSON_PRETTY_PRINT));
                $email->setFrom($this->helperConfig->getConfig('trans_email/ident_general/email'));
                $email->addTo($to);
                $email->send();
            } catch (Exception $exception) {
                $this->helperConfig->logError($exception->getMessage());
            }
        }
        return $order ?? null;
    }

    /**
     * @param Order $order
     * @param array $frequencies
     * @return array
     * @throws NoSuchEntityException
     */
    private function buildNewCartData(Order $order, array $frequencies)
    {
        $oldQuote = $this->quoteRepository->get($order->getQuoteId());
        $items = [];
        /** @var Order\Item $item */
        foreach ($order->getItemsCollection() as $item) {
            if ($item->getParentId()) {
                continue;
            }
            $cartItem = $oldQuote->getItemById($item->getQuoteItemId());
            $rebillCustomOption = $cartItem->getOptionByCode('rebill_subscription');
            if (!$rebillCustomOption) {
                continue;
            }
            $frequencyOption = json_decode($rebillCustomOption->getValue(), true);
            $frequency = [
                'frequency'      => $frequencyOption['frequency'] ?? 0,
                'frequency_type' => $frequencyOption['frequencyType'] ?? 'months',
            ];
            if (isset($frequencyOption['recurringPayments']) && $frequencyOption['recurringPayments'] > 0) {
                $frequency['recurring_payments'] = (int)$frequencyOption['recurringPayments'];
            }
            $frequencyHash = Transaction::createHashFromArray($frequency);
            if (!in_array($frequencyHash, $frequencies)) {
                continue;
            }
            $product = $this->productFactory->create()->load($item->getProductId());
            if (!$product->getId()) {
                $this->addError(__('Product %1 doen\'t exists anymore', $item->getSku()));
                continue;
            }
            if (!$product->isSalable()) {
                $this->addError(__('Product %1 is not salable', $product->getSku()));
                continue;
            }
            $items[] = [
                'order_item' => $item,
                'product'    => $product,
                'frequency'  => $frequencyOption,
            ];
        }
        return [
            'items'            => $items,
            'shipping_address' => $oldQuote->getShippingAddress(),
            'billing_address'  => $oldQuote->getBillingAddress(),
            'shipping_method'  => $order->getShippingMethod(),
            'shipping_costs'   => [
                'shipping_amount'                           => $order->getShippingAmount(),
                'shipping_tax_amount'                       => $order->getShippingTaxAmount(),
                'shipping_discount_amount'                  => $order->getShippingDiscountAmount(),
                'shipping_discount_tax_compensation_amount' => $order->getShippingDiscountTaxCompensationAmount(),
            ],
            'payment'          => $order->getPayment(),
            'payment_method'   => $order->getPayment()->getMethod(),
        ];
    }

    /**
     * @param string $message
     * @return void
     */
    private function addError(string $message)
    {
        $this->errors[] = $message;
    }

    /**
     * @param Item $orderItem
     * @param Quote $cart
     * @param Product $product
     * @param array $frequency
     * @return void
     * @throws LocalizedException
     */
    private function addItemToCart(
        Order\Item $orderItem,
        Quote      &$cart,
        Product    $product,
        array      $frequency
    ): void {
        $infoBuyRequest = $this->orderInfoBuyRequestGetter->getInfoBuyRequest($orderItem);
        $infoBuyRequest->setData('frequency', $frequency);
        $addProductResult = $cart->addProduct($product, $infoBuyRequest);
        // error happens in case the result is string
        if (is_string($addProductResult)) {
            $errors = array_unique(explode("\n", $addProductResult));
            foreach ($errors as $error) {
                $this->addCartItemErrorMessage($orderItem, $product, $error);
            }
        }
    }

    /**
     * @param Item $item
     * @param Product $product
     * @param string|null $message
     * @return void
     */
    private function addCartItemErrorMessage(Item $item, Product $product, string $message = null)
    {
        // try to get sku from line-item first.
        // for complex product type: if custom option is not available it can cause error
        $sku = $item->getSku() ?? $product->getData('sku');
        $this->addError($message
            ? __('Could not add the product with SKU "%1" to the shopping cart: %2', $sku, $message)
            : __('Could not add the product with SKU "%1" to the shopping cart', $sku));
    }
}
