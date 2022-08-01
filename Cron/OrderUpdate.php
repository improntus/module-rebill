<?php

namespace Improntus\Rebill\Cron;

use Improntus\Rebill\Helper\Config;
use Improntus\Rebill\Model\Rebill\Item;
use Improntus\Rebill\Model\ResourceModel\Subscription\Collection;
use Improntus\Rebill\Model\ResourceModel\Subscription\CollectionFactory;
use Improntus\Rebill\Model\Subscription;
use Magento\Catalog\Model\ProductRepository;
use Magento\Sales\Model\OrderRepository;

class OrderUpdate
{
    protected $collectionFactory;
    protected $orderRepository;
    protected $priceFactory;
    protected $configHelper;
    protected $productRepository;
    protected $rebillItem;

    public function __construct(
        CollectionFactory $collectionFactory,
        OrderRepository   $orderRepository,
        Config            $configHelper,
        ProductRepository $productRepository,
        Item              $rebillItem
    ) {
        $this->rebillItem = $rebillItem;
        $this->productRepository = $productRepository;
        $this->collectionFactory = $collectionFactory;
        $this->orderRepository = $orderRepository;
        $this->configHelper = $configHelper;
    }

    public function execute()
    {
        /** @var Collection $collection */
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('status', 'outdated');
        $collection->getSelect()->reset('columns');
        $collection->addFieldToSelect([
            'entity_id',
            'subscription_id',
            'price_id'
        ]);
        $collection->getSelect()->joinInner(
            ['rip' => 'rebill_item_price'],
            'rip.rebill_price_id = main_table.price_id',
            [
                'details_hash'  => 'rip.detail_hash',
                'details'       => 'rip.details',
                'order_id'      => 'rip.order_id',
                'order_item_id' => 'rip.order_item_id'
            ]
        );
        $collection->getSelect()->joinInner(
            ['ri' => 'rebill_item'],
            'ri.entity_id = rip.rebill_item_id',
            ['sku' => 'ri.product_sku',]
        );
        /**
         * @TODO in future implementation it will be needed the gateway in $rebillDetails
         */
        $gateway = $this->configHelper->getGatewayId();
        $currency = $this->configHelper->getCurrency();
        /** @var Subscription $subscription */
        foreach ($collection as $subscription) {
            $subscription->setData('status', 'processing')->save();
            $order = $this->orderRepository->get($subscription->getData('order_id'));
            $discount = 0;
            foreach ($order->getItems() as $item) {
                if ($item->getItemId() == $subscription->getData('order_item_id')) {
                    $discount = $item->getDiscountAmount() / $item->getQtyOrdered();
                }
            }
            $product = $this->productRepository->get($subscription->getData('sku'));
//            $oldDetails = json_decode($subscription->getData('details'));
            $priceData = [
                'amount'      => (string)$product->getFinalPrice() - $discount,
                'type'        => 'fixed',
                'repetitions' => null,
                'currency'    => $currency,
                'gatewayId'   => $gateway,
            ];
            $result = $this->rebillItem->updatePrice($subscription->getData('price_id'), $priceData);
            if ($result) {
                $subscription->setData('status', 'updated')->save();
            } else {
                $subscription->setData('status', 'outdated')->save();
            }
        }
    }
}
