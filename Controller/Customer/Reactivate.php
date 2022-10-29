<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2022 Improntus (http://www.improntus.com/)
 * @package Improntus_Rebill
 */

namespace Improntus\Rebill\Controller\Customer;

use Improntus\Rebill\Model\Entity\Subscription\Model as EntitySubscription;
use Improntus\Rebill\Model\Entity\Subscription\Repository as SubscriptionRepository;
use Improntus\Rebill\Model\Entity\SubscriptionShipment\Model as EntityShipment;
use Improntus\Rebill\Model\Entity\SubscriptionShipment\Repository as ShipmentRepository;
use Magento\Framework\Exception\CouldNotSaveException;

class Reactivate extends ChangeStatus
{
    /**
     * @param SubscriptionRepository|ShipmentRepository $repository
     * @param EntitySubscription|EntityShipment|null $subscription
     * @throws CouldNotSaveException
     */
    protected function changeStatus(
        SubscriptionRepository|ShipmentRepository $repository,
        EntitySubscription|EntityShipment|null $subscription = null
    )
    {
        if (is_null($subscription)) {
            return;
        }

        $_details = $subscription->getDetails();
        $this->rebillSubscription->updateSubscription(
            $subscription->getRebillId(),
            [
                'amount'      => $_details['price']['amount'],
                'repetitions' => $_details['remainingIterations'],
                'status'      => EntitySubscription::STATUS_ACTIVE,
                'card'        => $_details['card'],
            ]
        );
        $subscription->setStatus(EntitySubscription::STATUS_ACTIVE);
        $repository->save($subscription);
    }

    /**
     * @param EntitySubscription $subscription
     * @return bool
     */
    protected function canExecuteChange( EntitySubscription $subscription): bool
    {
        return $subscription->canReactivateIt();
    }

    /**
     * @return string
     */
    protected function getCantExecuteChangeMessage(): string
    {
        return __('Subscription cannot be reactivated.') . ' ' . parent::getCantExecuteChangeMessage();
    }

    /**
     * @return string
     */
    protected function getSuccessMessage(): string
    {
        return __('The subscription was reactivated.');
    }
}
