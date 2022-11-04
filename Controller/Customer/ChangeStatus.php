<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2022 Improntus (http://www.improntus.com/)
 * @package Improntus_Rebill
 */

namespace Improntus\Rebill\Controller\Customer;

use Exception;
use Improntus\Rebill\Helper\Config;
use Improntus\Rebill\Model\Entity\Queue\Repository as QueueRepository;
use Improntus\Rebill\Model\Entity\Subscription\Model as EntitySubscription;
use Improntus\Rebill\Model\Entity\Subscription\Repository as SubscriptionRepository;
use Improntus\Rebill\Model\Entity\SubscriptionShipment\Model as EntityShipment;
use Improntus\Rebill\Model\Entity\SubscriptionShipment\Repository as ShipmentRepository;
use Magento\Customer\Model\Session;
use Improntus\Rebill\Model\Rebill\Subscription as RebillSubscription;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;

abstract class ChangeStatus extends Action
{
    /**
     * @var RebillSubscription $rebillSubscription
     */
    protected $rebillSubscription;

    /**
     * @var string $customerEmail
     */
    protected $customerEmail;

    /**
     * @var Config $configHelper
     */
    protected $configHelper;

    /**
     * @var Session $session
     */
    protected $session;

    /**
     * @var SubscriptionRepository $subscriptionRepository
     */
    protected $subscriptionRepository;

    /**
     * @var ShipmentRepository $shipmentRepository
     */
    protected $shipmentRepository;

    /**
     * @var QueueRepository $queueRepository
     */
    private $queueRepository;

    /**
     * @param Context $context
     * @param RebillSubscription $rebillSubscription
     * @param Config $configHelper
     * @param Session $session
     * @param SubscriptionRepository $subscriptionRepository
     * @param ShipmentRepository $shipmentRepository
     */
    public function __construct(
        Context                $context,
        RebillSubscription     $rebillSubscription,
        Config                 $configHelper,
        Session                $session,
        SubscriptionRepository $subscriptionRepository,
        ShipmentRepository     $shipmentRepository,
        QueueRepository        $queueRepository
    ) {
        $this->subscriptionRepository = $subscriptionRepository;
        $this->shipmentRepository = $shipmentRepository;
        $this->session = $session;
        $this->configHelper = $configHelper;
        $this->rebillSubscription = $rebillSubscription;
        $this->queueRepository = $queueRepository;

        $this->customerEmail = ($this->session->getCustomer())
            ? $this->session->getCustomer()->getEmail()
            : '';

        parent::__construct($context);
    }

    /**
     * @throws Exception
     */
    public function execute()
    {
        if (!$this->configHelper->isLoggedIn()) {
            $this->messageManager->addWarningMessage(__('To enter this section you need to be logged in'));
            return $this->_redirect('customer/account/login');
        }

        $subscriptionPackage = $this->subscriptionRepository->getSubscriptionPackage(
            $this->getRequest()->getParam('id')
        );

        /** @var EntitySubscription $subscription */
        $subscription = $subscriptionPackage['subscription'];
        if (!$subscription || (!$this->canExecuteChange($subscription))) {
            $this->messageManager->addErrorMessage($this->getCantExecuteChangeMessage());
            return $this->_redirect('rebill/customer/subscriptions');
        }

        if (!$this->checkOutOfSync($subscription)) {
            $this->messageManager->addErrorMessage($this->getOutOfSyncMessage());
            return $this->_redirect('rebill/customer/subscriptions');
        }

        try {
            $this->changeStatus($this->subscriptionRepository, $subscription);
            $this->changeStatus($this->shipmentRepository, $subscriptionPackage['shipment']);
            foreach ($subscriptionPackage['subscription_list'] as $_subscription) {
                $this->changeStatus($this->subscriptionRepository, $_subscription);
            }
            $this->messageManager->addSuccessMessage($this->getSuccessMessage());
        } catch (Exception $exception) {
            $this->messageManager->addErrorMessage($this->getExceptionMessage());
        }

        return $this->_redirect('rebill/customer/subscriptions');
    }

    /**
     * @param EntitySubscription $subscription
     * @return bool
     */
    abstract protected function canExecuteChange(EntitySubscription $subscription): bool;

    /**
     * @return string
     */
    protected function getCantExecuteChangeMessage(): string
    {
        return __('Change cannot be made due to subscription status. Contact the store owner to get more information.');
    }

    /**
     * @param EntitySubscription $subscription
     * @return bool
     */
    protected function checkOutOfSync(EntitySubscription $subscription)
    {
//        $rebillSubscription = $this->rebillSubscription->getSubscription(
//            $subscription->getRebillId(),
//            $subscription->getDetails()['userEmail']
//        );
//
//        return ($subscription->getStatus() == $rebillSubscription['status']);

        $queues = $this->queueRepository->getEzList([
            'type'       => 'subscription_change_status',
            'status'     => 'pending',
            'parameters' => ['like' => '%"billingScheduleId"="' . $subscription->getRebillId() . '"%'],
        ]);

        return $queues && (count($queues->getItems()) == 0);
    }

    /**
     * @return string
     */
    protected function getOutOfSyncMessage(): string
    {
        return __('Change cannot be made right now. Try again in a few minutes.');
    }

    /**
     * @param SubscriptionRepository|ShipmentRepository $repository
     * @param EntitySubscription|EntityShipment|null $subscription
     */
    abstract protected function changeStatus($repository, $subscription = null);

    /**
     * @return string
     */
    protected function getSuccessMessage(): string
    {
        return __('The subscription status was changed.');
    }

    /**
     * @return string
     * @phpcs:disable
     */
    protected function getExceptionMessage(): string
    {
        return __('There was an error changing your subscription status. Contact the store owner to get more information.');
    }
}
