<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2022 Improntus (http://www.improntus.com/)
 * @package Improntus_Rebill
 */

namespace Improntus\Rebill\Controller\Adminhtml\Subscription;

use Exception;
use Magento\Backend\App\Action;
use Improntus\Rebill\Helper\Config;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Improntus\Rebill\Model\Rebill\Subscription;
use Improntus\Rebill\Model\Entity\Subscription\Model;
use Improntus\Rebill\Model\Entity\Subscription\Repository as SubscriptionRepository;
use Improntus\Rebill\Model\Entity\SubscriptionShipment\Repository as ShipmentRepository;

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
     * @param SubscriptionRepository $subscriptionRepository
     * @param ShipmentRepository $shipmentRepository
     */
    public function __construct(
        Context      $context,
        Subscription $subscription,
        Config       $configHelper,
        SubscriptionRepository $subscriptionRepository,
        ShipmentRepository     $shipmentRepository
    ) {
        $this->subscriptionRepository = $subscriptionRepository;
        $this->shipmentRepository = $shipmentRepository;
        $this->configHelper = $configHelper;
        $this->subscription = $subscription;
        parent::__construct($context);
    }

    /**
     * @return ResponseInterface
     */
    public function execute()
    {
        $rebillId = $this->getRequest()->getParam('rebill_id');
        $userEmail = $this->getRequest()->getParam('user_email');
        try {
            $subscription = $this->subscriptionRepository->getByRebillId($rebillId);
            $this->subscription->cancelSubscription($rebillId, $userEmail);
            $subscription->setStatus('CANCELLED');
            $this->subscriptionRepository->save($subscription);
            if ($subscription->getShipmentId()) {
                $shipment = $this->shipmentRepository->getById($subscription->getShipmentId());
                if ($shipment->getId()) {
                    $this->subscription->cancelSubscription($shipment->getRebillId(), $userEmail);
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
                $this->subscription->cancelSubscription($_subscription->getRebillId(), $userEmail);
                $_subscription->setStatus('CANCELLED');
                $this->subscriptionRepository->save($_subscription);
            }
            $this->messageManager->addSuccessMessage(__('The subscription was cancelled.'));
        } catch (Exception $exception) {
            $this->messageManager->addErrorMessage(__('There was an error cancelling the subscription. Error: %1', $exception->getMessage()));
        }
        return $this->_redirect('*/*/index');
    }
}
