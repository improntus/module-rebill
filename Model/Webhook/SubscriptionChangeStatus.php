<?php

namespace Improntus\Rebill\Model\Webhook;

use Improntus\Rebill\Model\Entity\Subscription\Repository as SubscriptionRepository;
use Improntus\Rebill\Model\Entity\SubscriptionShipment\Repository as ShipmentRepository;
use Improntus\Rebill\Model\Rebill\Subscription;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\FilterBuilder;

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
     * @param SubscriptionRepository $subscriptionRepository
     * @param ShipmentRepository $shipmentRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param FilterBuilder $filterBuilder
     * @param Subscription $rebillSubscription
     * @param array $parameters
     */
    public function __construct(
        private SubscriptionRepository $subscriptionRepository,
        private ShipmentRepository     $shipmentRepository,
        private SearchCriteriaBuilder  $searchCriteriaBuilder,
        private FilterBuilder          $filterBuilder,
        private Subscription           $rebillSubscription,
        array                          $parameters = []
    )
    {
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
        $subscription = $this->subscriptionRepository->getByRebillId($billingScheduleId);
        if (!$subscription) {
            return;
        }

        /*$isCyclic = !$subscription->getDetails()['price']['repetitions'];

        if ($newStatus === "FINISHED" && !$isCyclic) {
            return;
        }*/

        $filter[] = $this->filterBuilder
            ->setField('package_hash')
            ->setConditionType('eq')
            ->setValue($subscription->getPackageHash())
            ->create();

        $this->updateStatus($filter, $newStatus, $this->searchCriteriaBuilder, $this->subscriptionRepository);

        $filterShipment[] = $this->filterBuilder
            ->setField('entity_id')
            ->setConditionType('eq')
            ->setValue($subscription->getShipmentId())
            ->create();

        $this->updateStatus($filterShipment, $newStatus, $this->searchCriteriaBuilder, $this->shipmentRepository);
    }

    /**
     * @param array $filterShipment
     * @param string $newStatus
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param $repository
     * @return void
     * @throws \Exception
     */
    private function updateStatus(array $filterShipment, string $newStatus, SearchCriteriaBuilder $searchCriteriaBuilder, $repository)
    {
        $searchCriteria = $searchCriteriaBuilder->addFilters($filterShipment);
        $items = $repository->getList($searchCriteria->create());

        foreach ($items->getItems() as $item) {
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
}
