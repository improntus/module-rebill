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
use Improntus\Rebill\Model\Rebill\Subscription;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Improntus\Rebill\Model\Entity\SubscriptionShipment\Repository as ShipmentRepository;

class Save extends Action
{
    /**
     * @var Config
     */
    protected $configHelper;

    /**
     * @var Subscription
     */
    protected $rebillSubscription;

    /**
     * @var ShipmentRepository
     */
    protected $shipmentRepository;

    /**
     * @var SubscriptionRepository
     */
    protected $subscriptionRepository;

    /**
     * @param Context $context
     * @param Config $configHelper
     * @param Subscription $rebillSubscription
     * @param SubscriptionRepository $subscriptionRepository
     * @param ShipmentRepository $shipmentRepository
     */
    public function __construct(
        Context                $context,
        Config                 $configHelper,
        Subscription           $rebillSubscription,
        SubscriptionRepository $subscriptionRepository,
        ShipmentRepository     $shipmentRepository
    ) {
        $this->rebillSubscription = $rebillSubscription;
        $this->subscriptionRepository = $subscriptionRepository;
        $this->shipmentRepository = $shipmentRepository;
        $this->configHelper = $configHelper;
        parent::__construct($context);
    }

    /**
     * @return ResponseInterface|void
     * @throws Exception
     */
    public function execute()
    {
        if (!$this->configHelper->isLoggedIn()) {
            $this->messageManager->addWarningMessage(__('To enter this section you need to be logged in'));
            return $this->_redirect('customer/account/login');
        }
        try {
            $subscription = $this->subscriptionRepository->getByRebillId($this->getRequest()->getParam('id'));
            $rebillSubscription = $this->rebillSubscription->getSubscription(
                $this->getRequest()->getParam('id'),
                $subscription->getDetails()['userEmail']
            );
            $subscription->setDetails($rebillSubscription);
            $this->subscriptionRepository->save($subscription);
            if ($subscription->getShipmentId()) {
                $shipment = $this->shipmentRepository->getById($subscription->getShipmentId());
                if ($shipment->getId()) {
                    $shipmentDetails = $shipment->getDetails();
                    $this->rebillSubscription->updateSubscription(
                        $shipment->getRebillId(),
                        [
                            'amount'      => $shipmentDetails['price']['amount'],
                            'repetitions' => $shipmentDetails['remainingIterations'],
                            'status'      => $rebillSubscription['status'],
                            'card'        => $rebillSubscription['card'],
                        ]
                    );
                    $shipmentDetails['card'] = $rebillSubscription['card'];
                    $shipment->setDetails($shipmentDetails);
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
                $_subscriptionDetails = $_subscription->getDetails();
                $this->rebillSubscription->updateSubscription(
                    $_subscription->getRebillId(),
                    [
                        'amount'      => $_subscriptionDetails['price']['amount'],
                        'repetitions' => $_subscriptionDetails['remainingIterations'],
                        'status'      => $rebillSubscription['status'],
                        'card'        => $rebillSubscription['card'],
                    ]
                );
                $_subscriptionDetails['card'] = $rebillSubscription['card'];
                $_subscription->setDetails($_subscriptionDetails);
                $this->subscriptionRepository->save($_subscription);
            }
        } catch (Exception $exception) {
            $this->configHelper->logError($exception->getMessage());
            $this->messageManager->addErrorMessage(__('There was an error trying to update your subscription.'));
        }
        $this->_redirect('*/*/edit', ['id' => $this->getRequest()->getParam('id')]);
    }
}
