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
     * @param Context $context
     * @param Subscription $subscription
     * @param Config $configHelper
     */
    public function __construct(
        Context      $context,
        Subscription $subscription,
        Config       $configHelper
    ) {
        $this->configHelper = $configHelper;
        $this->subscription = $subscription;
        parent::__construct($context);
    }

    /**
     * @return ResponseInterface
     */
    public function execute()
    {
        $cancelId = $this->getRequest()->getParam('cancel_id');
        try {
            list($subscriptionId, $email) = explode('|', $cancelId);
            $this->subscription->cancelSubscription($subscriptionId, $email);
            $this->messageManager->addSuccessMessage(__('The subscription was cancelled.'));
        } catch (Exception $exception) {
            $this->messageManager->addErrorMessage(__('There was an error cancelling the subscription. Error: %1', $exception->getMessage()));
        }
        return $this->_redirect('*/*/index');
    }
}
