<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2022 Improntus (http://www.improntus.com/)
 * @package Improntus_Rebill
 */

namespace Improntus\Rebill\Controller\Payment;

use Exception;
use Improntus\Rebill\Helper\Config;
use Improntus\Rebill\Model\Webhook;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;

class Success extends Action
{
    /**
     * @var Config
     */
    protected $configHelper;

    /**
     * @var Webhook
     */
    protected $webhook;

    /**
     * @param Context $context
     * @param Config $configHelper
     * @param Webhook $webhook
     */
    public function __construct(
        Context $context,
        Config  $configHelper,
        Webhook $webhook
    ) {
        $this->webhook = $webhook;
        $this->configHelper = $configHelper;

        parent::__construct($context);
    }

    /**
     * @return void
     */
    public function execute()
    {
        try {
            $this->webhook->queueOrExecute('confirmation', $this->getRequest()->getParams());
        } catch (Exception $exception) {
            $this->messageManager->addErrorMessage($exception->getMessage());
            $this->configHelper->logError($exception->getMessage());
        }
        $this->_redirect('checkout/onepage/success');
    }
}
