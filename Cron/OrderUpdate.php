<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2022 Improntus (http://www.improntus.com/)
 * @package Improntus_Rebill
 */

namespace Improntus\Rebill\Cron;

use Exception;
use Magento\Sales\Model\Order;
use Improntus\Rebill\Helper\Config;
use Improntus\Rebill\Model\Rebill\Item;
use Magento\Quote\Model\QuoteRepository;
use Improntus\Rebill\Model\Sales\Reorder;
use Improntus\Rebill\Model\Sales\Invoice;
use Improntus\Rebill\Model\SubscriptionFactory;
use Magento\Framework\Exception\InputException;
use Improntus\Rebill\Model\Payment\Transaction;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\CouldNotSaveException;
use Improntus\Rebill\Model\ResourceModel\Subscription\Collection;
use Improntus\Rebill\Model\ResourceModel\Subscription\CollectionFactory;
use Improntus\Rebill\Model\Subscription;
use Magento\Catalog\Model\ProductRepository;
use Magento\Sales\Model\OrderRepository;
use Improntus\Rebill\Model\Rebill\Subscription as RebillSubscription;

class OrderUpdate
{
    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var OrderRepository
     */
    protected $orderRepository;

    /**
     * @var Config
     */
    protected $configHelper;

    /**
     * @var ProductRepository
     */
    protected $productRepository;

    /**
     * @var Item
     */
    protected $rebillItem;

    /**
     * @var Reorder
     */
    protected $reorder;

    /**
     * @var SubscriptionFactory
     */
    protected $subscriptionFactory;

    /**
     * @var Transaction
     */
    protected $transaction;

    /**
     * @var QuoteRepository
     */
    protected $quoteRepository;

    /**
     * @var RebillSubscription
     */
    protected $rebillSubscription;

    /**
     * @var Invoice
     */
    protected $invoice;

    /**
     * @param CollectionFactory $collectionFactory
     * @param OrderRepository $orderRepository
     * @param Config $configHelper
     * @param ProductRepository $productRepository
     * @param Item $rebillItem
     * @param Reorder $reorder
     * @param SubscriptionFactory $subscriptionFactory
     * @param Transaction $transaction
     * @param QuoteRepository $quoteRepository
     * @param RebillSubscription $rebillSubscription
     * @param Invoice $invoice
     */
    public function __construct(
        CollectionFactory   $collectionFactory,
        OrderRepository     $orderRepository,
        Config              $configHelper,
        ProductRepository   $productRepository,
        Item                $rebillItem,
        Reorder             $reorder,
        SubscriptionFactory $subscriptionFactory,
        Transaction         $transaction,
        QuoteRepository     $quoteRepository,
        RebillSubscription  $rebillSubscription,
        Invoice             $invoice
    ) {
        $this->invoice = $invoice;
        $this->rebillSubscription = $rebillSubscription;
        $this->quoteRepository = $quoteRepository;
        $this->transaction = $transaction;
        $this->subscriptionFactory = $subscriptionFactory;
        $this->reorder = $reorder;
        $this->rebillItem = $rebillItem;
        $this->productRepository = $productRepository;
        $this->collectionFactory = $collectionFactory;
        $this->orderRepository = $orderRepository;
        $this->configHelper = $configHelper;
    }

    /**
     * @return void
     * @throws CouldNotSaveException
     * @throws InputException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function execute()
    {
        /** @comment Get all the orders pending to update */
        /** @var Collection $ordersSubscriptions */
        $ordersSubscriptions = $this->collectionFactory->create();
        $ordersSubscriptions->addFieldToFilter('status', ['neq' => 'updated']);
        $ordersSubscriptions->addFieldToFilter('status', ['neq' => 'processing']);
        $ordersSubscriptions->getSelect()->group('order_id');
        /** @var Subscription $subscription */
        foreach ($ordersSubscriptions as $orderSubscription) {
            $this->processOrder($orderSubscription);
        }
    }

    /**
     * @param Subscription $orderSubscription
     * @return void
     * @throws CouldNotSaveException
     * @throws InputException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    protected function processOrder(Subscription $orderSubscription)
    {
        /** @comment Get all the subscriptions for the order */
        $orderId = $orderSubscription->getData('order_id');
        $subscriptions = $this->collectionFactory->create();
        $subscriptions->getSelect()->reset('columns');
        $subscriptions->addFieldToFilter('order_id', $orderId);
        /** @comment Update the subscriptions of the order to not be processed in a parallel cronjob in case of taking too much time */
        $connection = $subscriptions->getConnection();
        $connection->update($subscriptions->getMainTable(), ['status' => 'processing'], "order_id = $orderId");
        /** @comment Get the order */
        /** @var Order $order */
        $order = $this->orderRepository->get($orderSubscription->getData('order_id'));
        $status = $orderSubscription->getData('status');
        /** @comment $orderItems has the assign of item id with it item id match. If there's a new order, then item id will be equal to new order's item id */
        $orderItems = [];
        foreach ($order->getItems() as $item) {
            $orderItems[$item->getId()] = $item->getId();
        }
        /** @comment In case of "recalculate" prices for a new subscription payment, a new order has to be created */
        if ($status == 'recalculate') {
            $oldOrder = $order;
            /** @var Order $order */
            $order = $this->reorder->execute($order);
            $this->invoice->execute($order);
            /** @comment Match up the previous order items with the items in the new one */
            $oldOrderItems = [];
            /** @var Order\Item $item */
            foreach ($oldOrder->getItems() as $item) {
                $data = hash('md5', implode('-', $this->getItemData($item)));
                $oldOrderItems[$data] = $item->getId();
            }
            foreach ($order->getItems() as $item) {
                $data = hash('md5', implode('-', $this->getItemData($item)));
                $orderItems[$oldOrderItems[$data]] = $item->getId();
            }
        }
        /** @var Subscription $subscription */
        foreach ($subscriptions as $subscription) {
            try {
                /** @var Order\Item $orderItem */
                $orderItem = $order->getItemById($orderItems[$subscription->getData('order_item_id')]);
//                try {
//                    $priceId = $this->transaction->getRebillPriceIdForItem($orderItem, $this->quoteRepository->get($order->getQuoteId()));
//                } catch (Exception $exception) {
//                    $priceId = null;
//                }
                $result = null;
                if ($orderItem->getParentItemId()) {
                    $parentItem = $orderItem->getParentItem();
                    $rowTotal = $parentItem->getRowTotal()
                        - $parentItem->getDiscountAmount()
                        + $parentItem->getTaxAmount()
                        + $parentItem->getDiscountTaxCompensationAmount();
                    $orderItemQty = $parentItem->getQtyOrdered();
                } else {
                    $rowTotal = $orderItem->getRowTotal()
                        - $orderItem->getDiscountAmount()
                        + $orderItem->getTaxAmount()
                        + $orderItem->getDiscountTaxCompensationAmount();
                    $orderItemQty = $orderItem->getQtyOrdered();
                }
                $price = $rowTotal / $orderItemQty;
                if ($price) {
                    $priceData = [
                        'amount'      => (string)$price,
                        'type'        => 'fixed',
                        'repetitions' => null,
                        'currency'    => $this->configHelper->getCurrency(),
                        'gatewayId'   => $this->configHelper->getGatewayId(),
                    ];
                    $result = $this->rebillItem->updatePrice($subscription->getData('price_id'), $priceData);
                }
                if ($result) {
//                    if ($priceId != $subscription->getData('price_id')) {
//                        $this->rebillSubscription->changePrice($subscription->getData('subscription_id'), $priceId);
//                        $subscription->setData('price_id', $priceId);
//                    }
                    $subscription->setData('order_id', $order);
                    $subscription->setData('order_item_id', $orderItem->getId());
                    $subscription->setData('status', 'updated')->save();
                } else {
                    $subscription->setData('status', $status)->save();
                }
                /**
                 * @TODO change price id instead of updating the price for subscription
                 * There's no reason to update subscription price if we can assign a new price.
                 *
                 * Updating the existing price may cause problems in subscriptions that share that price with another subscription.
                 *
                 * Assigning a new price to the subscription is way easier thinking about shipping prices,
                 * cart rules, discounts and taxes may differ through different orders
                 */
            } catch (Exception $exception) {
                $this->configHelper->logError($exception);
            }
        }
    }

    /**
     * @param Order\Item $item
     * @return array
     */
    protected function getItemData(Order\Item $item)
    {
        return [
            'product_id'      => $item->getProductId(),
            'product_type'    => $item->getProductType(),
            'additional_data' => $item->getAdditionalData(),
            'sku'             => $item->getSku(),
            'product_options' => $item->getData('product_options'),
        ];
    }
}
