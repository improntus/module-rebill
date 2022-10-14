<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2022 Improntus (http://www.improntus.com/)
 * @package Improntus_Rebill
 */

namespace Improntus\Rebill\Controller\Customer;

use Exception;
use Improntus\Rebill\Helper\Config;
use Improntus\Rebill\Model\Entity\Subscription\Model;
use Improntus\Rebill\Model\Entity\Subscription\Repository as SubscriptionRepository;
use Improntus\Rebill\Model\Entity\SubscriptionShipment\Repository as ShipmentRepository;
use Magento\Customer\Model\Session;
use Improntus\Rebill\Model\Rebill\Subscription;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;

class Cancel extends Action
{
    /**
     * @var Subscription
     */
    protected $subscription;

    /**
     * @var Config
     */
    protected $configHelper;

    /**
     * @var Session
     */
    protected $session;

    /**
     * @var SubscriptionRepository
     */
    protected $subscriptionRepository;

    /**
     * @var ShipmentRepository
     */
    protected $shipmentRepository;

    /**
     * @param Context $context
     * @param Subscription $subscription
     * @param Config $configHelper
     * @param Session $session
     * @param SubscriptionRepository $subscriptionRepository
     * @param ShipmentRepository $shipmentRepository
     */
    public function __construct(
        Context                $context,
        Subscription           $subscription,
        Config                 $configHelper,
        Session                $session,
        SubscriptionRepository $subscriptionRepository,
        ShipmentRepository     $shipmentRepository
    ) {
        $this->subscriptionRepository = $subscriptionRepository;
        $this->shipmentRepository = $shipmentRepository;
        $this->session = $session;
        $this->configHelper = $configHelper;
        $this->subscription = $subscription;
        parent::__construct($context);
    }

    /**
     * @return ResponseInterface
     */
    public function execute()
    {
        if (!$this->configHelper->isLoggedIn()) {
            $this->messageManager->addWarningMessage(__('To enter this section you need to be logged in'));
            return $this->_redirect('customer/account/login');
        }
        $subscriptionId = $this->getRequest()->getParam('id');
        $subscription = $this->subscriptionRepository->getByRebillId($this->getRequest()->getParam('id'));
        $customerEmail = $this->session->getCustomer()->getEmail();
        try {
            $this->subscription->cancelSubscription($subscriptionId, $customerEmail);
            $subscription->setStatus('CANCELLED');
            $this->subscriptionRepository->save($subscription);
            if ($subscription->getShipmentId()) {
                $shipment = $this->shipmentRepository->getById($subscription->getShipmentId());
                if ($shipment->getId()) {
                    $this->subscription->cancelSubscription($shipment->getRebillId(), $customerEmail);
                    $shipment->setStatus('CANCELLED');
                    $this->shipmentRepository->save($shipment);
                }
            }
            $package = $this->subscriptionRepository->getCollection();
            $package->addFieldToFilter('package_hash', $subscription->getPackageHash());
            /** @var Model $_subscription */
            foreach ($package as $_subscription) {
                if ($_subscription->getId() == $subscription->getId()) {
                    continue;
                }
                $this->subscription->cancelSubscription($_subscription->getRebillId(), $customerEmail);
                $_subscription->setStatus('CANCELLED');
                $this->subscriptionRepository->save($_subscription);
            }
            $this->messageManager->addSuccessMessage(__('The subscription was cancelled.'));
        } catch (Exception $exception) {
            $this->messageManager->addErrorMessage(__('There was an error cancelling your subscription, contact the store owner to get more information.'));
        }
        return $this->_redirect('rebill/customer/subscriptions');
    }
}
