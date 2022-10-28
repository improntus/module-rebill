<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2022 Improntus (http://www.improntus.com/)
 * @package Improntus_Rebill
 */

namespace Improntus\Rebill\Model\Webhook;

use Exception;
use Improntus\Rebill\Helper\Config;
use Improntus\Rebill\Model\Entity\Price\Model;
use Improntus\Rebill\Model\Entity\Price\Repository as PriceRepository;
use Improntus\Rebill\Model\Entity\Subscription\Repository as SubscriptionRepository;
use Improntus\Rebill\Model\Entity\SubscriptionShipment\Repository as ShipmentRepository;
use Improntus\Rebill\Model\Rebill\Subscription;
use Improntus\Rebill\Model\Sales\Reorder;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
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
            $this->executeHeadsUp($subscriptionId);
        } catch (Exception $exception) {
            $this->configHelper->logError($exception->getMessage());
        }
    }

    /**
     * @param string $rebillSubscriptionId
     * @param bool $force
     * @return void
     * @throws CouldNotSaveException
     * @throws InputException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function executeHeadsUp(string $rebillSubscriptionId, bool $force = false)
    {
        $package = $this->subscriptionRepository->getSubscriptionPackage($rebillSubscriptionId);
        /** @var \Improntus\Rebill\Model\Entity\Subscription\Model $subscription */
        $subscription = $package['subscription'];
        $_order = $this->orderRepository->get($subscription->getOrderId());
        $rebillSubscription = $this->subscriptionRepository->getRebillSubscription(
            $rebillSubscriptionId,
            $_order->getCustomerEmail()
        );
        if (($rebillSubscription['status'] !== 'ACTIVE'
                || $rebillSubscription['nextChargeDate'] == $subscription->getNextSchedule())
            && !$force) {
            return;
        }
        $hashes = [];
        /** @var \Improntus\Rebill\Model\Entity\Subscription\Model $sub */
        foreach ($package['subscription_list'] as $sub) {
            /** @var Model $price */
            $price = $sub->getData('price');
            $details = $price->getDetails();
            $hashes[$details['sku']] = $price->getFrequencyHash();
        }
        /** @var Order $_order */
        $order = $this->reorder->execute($_order, $hashes);
        /** @var \Improntus\Rebill\Model\Entity\SubscriptionShipment\Model $shipment */
        if ($shipment = $package['shipment']) {
            if ($shipment->getId()) {
                $shippingPrice = array_sum([
                    $order->getShippingAmount(),
                    $order->getShippingTaxAmount(),
                    -$order->getShippingDiscountAmount(),
                    -$order->getShippingDiscountTaxCompensationAmount(),
                ]);
                $this->rebillSubscription->updateSubscription(
                    $shipment->getRebillId(),
                    [
                        'amount' => $shippingPrice,
                        'card' => $rebillSubscription['card'],
                        'nextChargeDate' => $rebillSubscription['nextChargeDate'],
                        'status' => $rebillSubscription['status'],
                    ]
                );
                $shipment->setPayed(0);
                $shipment->setOrderId($order->getId());
                $shipment->setNextSchedule($rebillSubscription['nextChargeDate']);
                if ($shipment->getId() == $subscription->getId()) {
                    $shipment->setDetails($rebillSubscription);
                }
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
                'frequency'      => $_frequencyQty ?? 0,
                'frequency_type' => $frequencyOption['frequencyType'] ?? 'months',
            ];
            if (isset($frequencyOption['recurringPayments']) && $frequencyOption['recurringPayments']) {
                $frequency['recurring_payments'] = (int)$frequencyOption['recurringPayments'];
            }
            $frequencyHash = hash('md5', implode('-', $frequency));
            /** @var \Improntus\Rebill\Model\Entity\Subscription\Model $sub */
            foreach ($package['subscription_list'] as $sub) {
                /** @var Model $price */
                $price = $sub->getData('price');
                $options = $price->getDetails();
                if ($price->getData('frequency_hash') != $frequencyHash
                    || $orderItem->getSku() != $options['sku']) {
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
                    $sub->getRebillId(),
                    [
                        'amount' => $price,
                        'card' => $rebillSubscription['card'],
                        'nextChargeDate' => $rebillSubscription['nextChargeDate'],
                        'status' => $rebillSubscription['status'],
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
}
