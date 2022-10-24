<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2022 Improntus (http://www.improntus.com/)
 * @package Improntus_Rebill
 */

namespace Improntus\Rebill\Controller\Notification;

use Improntus\Rebill\Model\Webhook;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;

class SubscriptionChangeStatus extends Action implements HttpGetActionInterface, HttpPostActionInterface
{
    /**
     * @param Context $context
     * @param Webhook $webhook
     */
    public function __construct(
        Context $context,
        Webhook $webhook
    ) {
        $this->webhook = $webhook;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|void
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function execute()
    {
        try {
            $this->webhook->queueOrExecute('subscription_change_status', $this->getRequest()->getParams());
        } catch (Exception $exception) {
            $this->messageManager->addErrorMessage($exception->getMessage());
        }
    }
}
