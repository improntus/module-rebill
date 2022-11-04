<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2022 Improntus (http://www.improntus.com/)
 * @package Improntus_Rebill
 */

namespace Improntus\Rebill\Controller\Adminhtml\Subscription;

use Exception;
use Improntus\Rebill\Helper\Config;
use Improntus\Rebill\Model\Entity\Subscription\Model as EntitySubscription;
use Improntus\Rebill\Model\Entity\Subscription\Repository as SubscriptionRepository;
use Improntus\Rebill\Model\Entity\SubscriptionShipment\Model as EntityShipment;
use Improntus\Rebill\Model\Entity\SubscriptionShipment\Repository as ShipmentRepository;
use Improntus\Rebill\Model\Rebill\Subscription as RebillSubscription;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Customer\Model\Session;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Exception\CouldNotSaveException;

class Cancel extends Action
{
    /**
     * @var RebillSubscription $rebillSubscription
     */
    protected RebillSubscription $rebillSubscription;

    /**
     * @var string $customerEmail
     */
    protected string $customerEmail;

    /**
     * @var Config $configHelper
     */
    protected Config $configHelper;

    /**
     * @var Session $session
     */
    protected Session $session;

    /**
     * @var SubscriptionRepository $subscriptionRepository
     */
    protected SubscriptionRepository $subscriptionRepository;

    /**
     * @var ShipmentRepository $shipmentRepository
     */
    protected ShipmentRepository $shipmentRepository;

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
        ShipmentRepository     $shipmentRepository
    ) {
        $this->subscriptionRepository = $subscriptionRepository;
        $this->shipmentRepository = $shipmentRepository;
        $this->session = $session;
        $this->configHelper = $configHelper;
        $this->rebillSubscription = $rebillSubscription;

        $this->customerEmail = '';

        parent::__construct($context);
    }

    /**
     * @return ResponseInterface
     * @throws Exception
     */
    public function execute()
    {
        $this->customerEmail = $this->getRequest()->getParam('user_email');

        $subscriptionPackage = $this->subscriptionRepository->getSubscriptionPackage($this->getRequest()->getParam('rebill_id'));

        /** @var EntitySubscription $subscription */
        $subscription = $subscriptionPackage['subscription'];
        if (is_null($subscription) || (!$this->canExecuteChange($subscription))) {
            $this->messageManager->addErrorMessage($this->getCantExecuteChangeMessage());
            return $this->_redirect('*/*/index');
        }

        try {
            $this->changeStatus($this->subscriptionRepository, $subscription);
            $this->changeStatus($this->shipmentRepository, $subscriptionPackage['shipment']);
            foreach ($subscriptionPackage['subscription_list'] as $_subscription) {
                $this->changeStatus($this->subscriptionRepository, $_subscription);
            }
            $this->messageManager->addSuccessMessage(__('The subscription was cancelled.'));
        } catch (Exception $exception) {
            $this->messageManager->addErrorMessage(__('There was an error cancelling the subscription. Error: %1', $exception->getMessage()));
        }
        return $this->_redirect('*/*/index');
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
     * @param SubscriptionRepository|ShipmentRepository $repository
     * @param EntitySubscription|EntityShipment|null $subscription
     * @throws CouldNotSaveException
     * @throws Exception
     */
    protected function changeStatus(
        SubscriptionRepository|ShipmentRepository $repository,
        EntitySubscription|EntityShipment|null    $subscription = null
    ) {
        if (is_null($subscription)) {
            return;
        }
        $this->rebillSubscription->cancelSubscription($subscription->getRebillId(), $this->customerEmail);
        $subscription->setStatus(EntitySubscription::STATUS_CANCELLED);
        $repository->save($subscription);
    }
}
