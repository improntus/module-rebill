<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2022 Improntus (http://www.improntus.com/)
 * @package Improntus_Rebill
 */

namespace Improntus\Rebill\Controller\Notification;

use Exception;
use Improntus\Rebill\Model\Webhook;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;

/**
 * @description Webhook for new payment
 */
class NewPayment extends Action implements HttpGetActionInterface, HttpPostActionInterface
{
    /**
     * @var Webhook
     */
    protected $webhook;

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
     * @return ResponseInterface|ResultInterface|void
     * @throws Exception
     */
    public function execute()
    {
        try {
            $this->webhook->queueOrExecute('new_payment', $this->getRequest()->getParams());
        } catch (Exception $exception) {
            $this->messageManager->addErrorMessage($exception->getMessage());
        }
    }
}
