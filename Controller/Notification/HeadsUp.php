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
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;

/**
 * @description Webhook for 24 hours heads up before new payment in a recurrent or cycling subscription
 */
class HeadsUp extends Action implements HttpGetActionInterface
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
        Context             $context,
        Webhook             $webhook
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
            $this->webhook->queueOrExecute('heads_up', $this->getRequest()->getParams());
        } catch (Exception $exception) {
            $this->messageManager->addErrorMessage($exception->getMessage());
        }
    }
}
