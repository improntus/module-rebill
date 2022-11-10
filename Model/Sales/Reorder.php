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
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Mail\Message;
use Magento\Framework\Mail\TransportInterfaceFactory;
use Magento\Framework\Model\AbstractExtensibleModel;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Model\Cart\CustomerCartResolver;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\QuoteManagement;
use Magento\Quote\Model\QuoteRepository;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Item;
use Magento\Sales\Model\Reorder\OrderInfoBuyRequestGetter;

class Reorder
{
    private const SUBSTITUTE_SHIPPING_METHOD = 'flatrate_flatrate';

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
     * @var TransportInterfaceFactory
     */
    private $mailTransportFactory;

    /**
     * @var array
     */
    private $errors = [];

    /**
     * @param QuoteRepository $quoteRepository
     * @param CartManagementInterface $cartManagement
     * @param ProductFactory $productFactory
     * @param CustomerCartResolver $customerCartProvider
     * @param OrderInfoBuyRequestGetter $orderInfoBuyRequestGetter
     * @param TransportInterfaceFactory $mailTransportFactory
     * @param Config $helperConfig
     */
    public function __construct(
        QuoteRepository              $quoteRepository,
        CartManagementInterface      $cartManagement,
        ProductFactory               $productFactory,
        CustomerCartResolver         $customerCartProvider,
        OrderInfoBuyRequestGetter    $orderInfoBuyRequestGetter,
        TransportInterfaceFactory    $mailTransportFactory,
        Config                       $helperConfig
    ) {
        $this->mailTransportFactory = $mailTransportFactory;
        $this->helperConfig = $helperConfig;
        $this->customerCartProvider = $customerCartProvider;
        $this->orderInfoBuyRequestGetter = $orderInfoBuyRequestGetter;
        $this->productFactory = $productFactory;
        $this->quoteRepository = $quoteRepository;
        $this->cartManagement = $cartManagement;
    }

    /**
     * @param Order $_order
     * @param array $frequencies
     * @param string $subscription
     * @param int|null $queueId
     * @return array|AbstractExtensibleModel|OrderInterface|object|null
     * @throws CouldNotSaveException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function execute(Order $_order, array $frequencies, string $subscription, ?int $queueId)
    {
        $newCartData = $this->buildNewCartData($_order, $frequencies);
        if (!$newCartData['items']) {
            return null;
        }
        $newCart = $this->customerCartProvider->resolve($_order->getCustomerId());
        $newCart->removeAllItems();
        foreach ($newCart->getAllAddresses() as $address) {
            $address->delete();
        }
        $newCart->removeAllAddresses();
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
            if (!isset($order) || !$order) {
                $this->quoteRepository->delete($newCart);
            }
            try {
                $this->addError(__('Order #%1', $_order->getIncrementId()));
                $this->addError(__('Subscription: %1', $subscription));
                if ($queueId) {
                    $this->addError(__('Queue Id: %1', $queueId));
                }
                $message = new Message();
                $message->setFromAddress($this->helperConfig->getConfig('trans_email/ident_general/email'));
                $message->addTo($this->helperConfig->getFailedReorderEmail());
                $message->setSubject("Subscription Reorder Failed");
                $message->setBodyText(json_encode($this->errors, JSON_PRETTY_PRINT));
                $transport = $this->mailTransportFactory->create(['message' => $message]);
                $transport->sendMessage();
                $this->helperConfig->logInfo('Reorder Failed. Email Sended.');
            } catch (Exception $exception) {
                $this->helperConfig->logError($exception->getMessage());
            }
        }
        return $order ?? $this->errors;
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
                $this->addError(__('Product %1 doesn\'t exists anymore', $item->getSku()));
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
        $shippingAddress = clone $oldQuote->getShippingAddress();
        $billingAddress = clone $oldQuote->getBillingAddress();
        $shippingAddress->setData('address_id', null);
        $shippingAddress->setData('quote_id', null);
        $billingAddress->setData('address_id', null);
        $billingAddress->setData('quote_id', null);
        $payment = clone $oldQuote->getPayment();
        $payment->setData('payment_id', null);
        $payment->setData('quote_id', null);
        return [
            'items'            => $items,
            'shipping_address' => $shippingAddress,
            'billing_address'  => $billingAddress,
            'shipping_method'  => $order->getShippingMethod(),
            'shipping_costs'   => [
                'shipping_amount'                           => $order->getShippingAmount(),
                'shipping_tax_amount'                       => $order->getShippingTaxAmount(),
                'shipping_discount_amount'                  => $order->getShippingDiscountAmount(),
                'shipping_discount_tax_compensation_amount' => $order->getShippingDiscountTaxCompensationAmount(),
            ],
            'payment'          => $payment,
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
        /** @comment Set initialCost 0 to avoid wrong total calculation */
        $frequency['initialCost'] = 0;
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
