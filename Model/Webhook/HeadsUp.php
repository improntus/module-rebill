<?php

namespace Improntus\Rebill\Model\Webhook;

use Exception;
use Improntus\Rebill\Helper\Config;
use Improntus\Rebill\Model\Entity\Price\Model;
use Improntus\Rebill\Model\Entity\Price\Repository as PriceRepository;
use Improntus\Rebill\Model\Entity\Subscription\Repository as SubscriptionRepository;
use Improntus\Rebill\Model\Entity\SubscriptionShipment\Repository as ShipmentRepository;
use Improntus\Rebill\Model\Rebill\Subscription;
use Improntus\Rebill\Model\Sales\Reorder;
use Magento\Quote\Model\QuoteRepository;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderRepository;
use Zend_Db_Expr;

class HeadsUp extends WebhookAbstract
{
    /**
     * @var Config
     */
    protected $configHelper;

    /**
     * @var OrderRepository
     */
    protected $orderRepository;

    /**
     * @var SubscriptionRepository
     */
    protected $subscriptionRepository;

    /**
     * @var ShipmentRepository
     */
    protected $shipmentRepository;

    /**
     * @var Reorder
     */
    protected $reorder;

    /**
     * @var PriceRepository
     */
    protected $priceRepository;

    /**
     * @var QuoteRepository
     */
    protected $quoteRepository;

    /**
     * @var Subscription
     */
    protected $rebillSubscription;

    /**
     * @param Config $configHelper
     * @param OrderRepository $orderRepository
     * @param SubscriptionRepository $subscriptionRepository
     * @param ShipmentRepository $shipmentRepository
     * @param PriceRepository $priceRepository
     * @param QuoteRepository $quoteRepository
     * @param Reorder $reorder
     * @param Subscription $rebillSubscription
     * @param array $parameters
     */
    public function __construct(
        Config                 $configHelper,
        OrderRepository        $orderRepository,
        SubscriptionRepository $subscriptionRepository,
        ShipmentRepository     $shipmentRepository,
        PriceRepository        $priceRepository,
        QuoteRepository        $quoteRepository,
        Reorder                $reorder,
        Subscription           $rebillSubscription,
        array                  $parameters = []
    ) {
        $this->rebillSubscription = $rebillSubscription;
        $this->quoteRepository = $quoteRepository;
        $this->reorder = $reorder;
        $this->configHelper = $configHelper;
        $this->orderRepository = $orderRepository;
        $this->subscriptionRepository = $subscriptionRepository;
        $this->shipmentRepository = $shipmentRepository;
        $this->priceRepository = $priceRepository;
        parent::__construct($parameters);
    }

    /**
     * @return mixed|void
     */
    public function execute()
    {
        try {
            $subscriptionId = $this->getParameter('id');
            $subscription = $this->subscriptionRepository->getByRebillId($subscriptionId);
            if ($subscription->getId()) {
                $order = $this->orderRepository->get($subscription->getOrderId());
                $rebillSubscription = $this->subscriptionRepository->getRebillSubscription(
                    $subscriptionId,
                    $order->getCustomerEmail()
                );
                if ($rebillSubscription['status'] !== 'ACTIVE'
                    || $rebillSubscription['nextChargeDate'] == $subscription->getNextSchedule()) {
                    return;
                }
                $packageHash = $subscription->getPackageHash();
                $package = $this->subscriptionRepository->getCollection();
                $package->addFieldToFilter('package_hash', $packageHash);
                $package->getSelect()->joinInner(
                    ['rip' => 'rebill_item_price'],
                    'rip.rebill_price_id = main_table.rebill_price_id',
                    [
                        'frequency_hash' => 'rip.frequency_hash',
                        'sku'            => new Zend_Db_Expr("JSON_UNQUOTE(JSON_EXTRACT(rip.details, '$.sku'))"),
                    ]
                );
                $hashes = [];
                foreach ($package as $sub) {
                    $hashes[$sub->getData('sku')] = $sub->getData('frequency_hash');
                }
                /** @var Order $order */
                $order = $this->reorder->execute($order, $hashes);
                if ($subscription->getShipmentId()) {
                    $shipment = $this->shipmentRepository->getById($subscription->getShipmentId());
                    if ($shipment->getId()) {
                        $shippingPrice = array_sum([
                            $order->getShippingAmount(),
                            $order->getShippingTaxAmount(),
                            -$order->getShippingDiscountAmount(),
                            -$order->getShippingDiscountTaxCompensationAmount(),
                        ]);
                        $this->rebillSubscription->updateSubscription(
                            $rebillSubscription['id'],
                            [
                                'amount'         => $shippingPrice,
                                'card'           => $rebillSubscription['card'],
                                'nextChargeDate' => $rebillSubscription['nextChargeDate'],
                                'status'         => $rebillSubscription['status'],
                            ]
                        );
                        $shipment->setPayed(0);
                        $shipment->setOrderId($order->getId());
                        $this->shipmentRepository->save($shipment);
                    }
                }
                $quote = $this->quoteRepository->get($order->getQuoteId());
                /** @var Order\Item $orderItem */
                foreach ($order->getAllVisibleItems() as $orderItem) {
                    $quoteItem = $quote->getItemById($orderItem->getQuoteItemId());
                    $frequencyOption = $quoteItem->getOptionByCode('rebill_subscription');
                    $frequencyOption = json_decode($frequencyOption->getValue(), true);
                    $_frequencyQty = $frequencyOption['frequency'] ?? 0;
                    $frequency = [
                        'frequency'          => $_frequencyQty ?? 0,
                        'frequency_type'     => $frequencyOption['frequencyType'] ?? 'months',
                        'recurring_payments' => 1,
                    ];
                    if (isset($frequencyOption['recurringPayments']) && $frequencyOption['recurringPayments'] > 0) {
                        $frequency['recurring_payments'] = (int)$frequencyOption['recurringPayments'];
                    }
                    $frequencyHash = hash('md5', implode('-', $frequency));
                    /** @var \Improntus\Rebill\Model\Entity\Subscription\Model $sub */
                    foreach ($package as $sub) {
                        if ($sub->getData('frequency_hash') != $frequencyHash
                            || $orderItem->getSku() != $sub->getData('sku')) {
                            continue;
                        }
                        $discount = $orderItem->getDiscountAmount();
                        $rowTotal = array_sum([
                            $orderItem->getRowTotal(),
                            $discount > 0 ? $discount * -1 : $discount,
                            $orderItem->getTaxAmount(),
                            $orderItem->getDiscountTaxCompensationAmount(),
                        ]);
                        $itemQty = $orderItem->getQtyOrdered();
                        $price = $rowTotal / $itemQty;
                        $this->rebillSubscription->updateSubscription(
                            $rebillSubscription['id'],
                            [
                                'amount'         => $price,
                                'card'           => $rebillSubscription['card'],
                                'nextChargeDate' => $rebillSubscription['nextChargeDate'],
                                'status'         => $rebillSubscription['status'],
                            ]
                        );
                        $sub->setNextSchedule($rebillSubscription['nextChargeDate']);
                        $sub->setOrderId($order->getId());
                        $sub->setPayed(0);
                        if ($sub->getId() == $subscription->getId()) {
                            $sub->setDetails($rebillSubscription);
                        }
                        $this->subscriptionRepository->save($sub);
                    }
                }
            }
        } catch (Exception $exception) {
            $this->configHelper->logError($exception->getMessage());
        }
    }
}
