<?php

namespace Improntus\Rebill\Model\Webhook;

use Improntus\Rebill\Model\Entity\Subscription\Repository as SubscriptionRepository;
use Improntus\Rebill\Model\Rebill\Subscription;

class SubscriptionChangeStatus extends WebhookAbstract
{
    const ENABLED_STATES = [
        'ACTIVE',
        'PAUSED',
        'DEFAULT',
        'CANCELLED',
        'FINISHED',
        'RETRYING',
        'WAITING_FOR_GATEWAY'
    ];
    /**
     * @var SubscriptionRepository
     */
    private SubscriptionRepository $subscriptionRepository;
    /**
     * @var Subscription
     */
    private Subscription $rebillSubscription;

    /**
     * @param SubscriptionRepository $subscriptionRepository
     * @param Subscription $rebillSubscription
     * @param array $parameters
     */
    public function __construct(
        SubscriptionRepository $subscriptionRepository,
        Subscription           $rebillSubscription,
        array                  $parameters = []
    )
    {
        $this->subscriptionRepository = $subscriptionRepository;
        $this->rebillSubscription = $rebillSubscription;
        parent::__construct($parameters);
    }

    /**
     * @return mixed|void
     * @throws \Exception
     */
    public function execute()
    {
        $billingScheduleId = $this->getParameter('billingScheduleId');
        $newStatus = $this->getParameter('newStatus');

        if (empty($newStatus) || empty($billingScheduleId) || !in_array(strtoupper($newStatus), self::ENABLED_STATES)) {
            return;
        }

        $newStatus = strtoupper($newStatus);

        $shipmentPackage = $this->subscriptionRepository->getSubscriptionPackage($billingScheduleId);

        foreach ($shipmentPackage["subscription_list"] as $subscription) {
            $this->updateStatus($subscription, $newStatus);
        }

        $this->updateStatus($shipmentPackage["shipment"], $newStatus);
    }

    private function updateStatus($item, $newStatus)
    {
        $item->setStatus($newStatus);
        $item->save();
        $details = $item->getDetails();
        $this->rebillSubscription->updateSubscription(
            $item->getRebillId(),
            [
                'amount' => $details["price"]["amount"],
                'card' => $details['card'],
                'nextChargeDate' => $details['nextChargeDate'],
                'status' => $newStatus,
            ]
        );
    }
}
