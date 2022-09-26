<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2022 Improntus (http://www.improntus.com/)
 * @package Improntus_Rebill
 */

namespace Improntus\Rebill\Controller\Customer;

use Exception;
use Improntus\Rebill\Helper\Config;
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
     * @param Context $context
     * @param Subscription $subscription
     * @param Config $configHelper
     * @param Session $session
     */
    public function __construct(
        Context      $context,
        Subscription $subscription,
        Config       $configHelper,
        Session      $session
    ) {
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
        $customerEmail = $this->session->getCustomer()->getEmail();
        try {
            $this->subscription->cancelSubscription($subscriptionId, $customerEmail);
            $this->messageManager->addSuccessMessage(__('The subscription was cancelled.'));
        } catch (Exception $exception) {
            $this->messageManager->addErrorMessage(__('There was an error cancelling your subscription, contact the store owner to get more information.'));
        }
        return $this->_redirect('rebill/customer/subscriptions');
    }
}
