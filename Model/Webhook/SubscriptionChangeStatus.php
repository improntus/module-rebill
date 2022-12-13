<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2022 Improntus (http://www.improntus.com/)
 * @package Improntus_Rebill
 */

namespace Improntus\Rebill\Model\Webhook;

use Exception;
use Improntus\Rebill\Model\Entity\Subscription\Model as SubscriptionModel;
use Improntus\Rebill\Model\Entity\Subscription\Repository as SubscriptionRepository;
use Improntus\Rebill\Model\Entity\SubscriptionShipment\Model as ShipmentModel;
use Improntus\Rebill\Model\Rebill\Subscription;
use Magento\Framework\Exception\CouldNotSaveException;

class SubscriptionChangeStatus extends WebhookAbstract
{
    private const ENABLED_STATES = [
        'ACTIVE',
        'PAUSED',
        'DEFAULT',
        'CANCELLED',
        'FINISHED',
        'RETRYING',
        'WAITING_FOR_GATEWAY',
    ];
    /**
     * @var SubscriptionRepository
     */
    private $subscriptionRepository;
    /**
     * @var Subscription
     */
    private $rebillSubscription;

    /**
     * @param SubscriptionRepository $subscriptionRepository
     * @param Subscription $rebillSubscription
     * @param array $parameters
     */
    public function __construct(
        SubscriptionRepository $subscriptionRepository,
        Subscription           $rebillSubscription,
        array                  $parameters = []
    ) {
        $this->subscriptionRepository = $subscriptionRepository;
        $this->rebillSubscription = $rebillSubscription;
        parent::__construct($parameters);
    }

    /**
     * @return mixed|void
     * @throws Exception
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

    /**
     * @param SubscriptionModel|ShipmentModel $item
     * @param string $newStatus
     * @return void
     * @throws CouldNotSaveException
     */
    private function updateStatus($item, string $newStatus)
    {
        if ($newStatus === $item->getStatus()) {
            return;
        }
        $item->setStatus($newStatus);
        if ($item instanceof SubscriptionModel) {
            $this->subscriptionRepository->save($item);
        } else {
            $this->subscriptionRepository->getSubscriptionShipmentRepository()->save($item);
        }
        $details = $item->getDetails();
        $this->rebillSubscription->updateSubscription(
            $item->getRebillId(),
            [
                'status' => $newStatus,
            ]
        );
    }
}
