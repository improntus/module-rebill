<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2022 Improntus (http://www.improntus.com/)
 * @package Improntus_Rebill
 */

namespace Improntus\Rebill\Controller\Customer;

use Exception;
use Improntus\Rebill\Model\Entity\Subscription\Model as EntitySubscription;
use Improntus\Rebill\Model\Entity\Subscription\Repository as SubscriptionRepository;
use Improntus\Rebill\Model\Entity\SubscriptionShipment\Model as EntityShipment;
use Improntus\Rebill\Model\Entity\SubscriptionShipment\Repository as ShipmentRepository;
use Magento\Framework\Exception\CouldNotSaveException;

class Cancel extends ChangeStatus
{
    /**
     * @param SubscriptionRepository|ShipmentRepository $repository
     * @param EntitySubscription|EntityShipment|null $subscription
     * @throws CouldNotSaveException
     * @throws Exception
     */
    protected function changeStatus(
        $repository,
        $subscription = null
    ) {
        if (!$subscription) {
            return;
        }
        $this->rebillSubscription->cancelSubscription($subscription->getRebillId(), $this->customerEmail);
        $subscription->setStatus(EntitySubscription::STATUS_CANCELLED);
        $repository->save($subscription);
    }

    /**
     * @param EntitySubscription $subscription
     * @return bool
     */
    protected function canExecuteChange(EntitySubscription $subscription): bool
    {
        return $subscription->canCancelIt();
    }

    /**
     * @return string
     */
    protected function getCantExecuteChangeMessage(): string
    {
        return __('Subscription cannot be cancelled.') . ' ' . parent::getCantExecuteChangeMessage();
    }

    /**
     * @return string
     */
    protected function getSuccessMessage(): string
    {
        return __('The subscription was cancelled.');
    }
}
