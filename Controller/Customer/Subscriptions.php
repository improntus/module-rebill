<?php

namespace Improntus\Rebill\Controller\Customer;

use Improntus\Rebill\Helper\Config;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;

class Subscriptions extends Action
{
    protected $configHelper;

    public function __construct(
        Context $context,
        Config $configHelper
    ) {
        $this->configHelper = $configHelper;
        parent::__construct($context);
    }

    /**
     * @return ResponseInterface|void
     */
    public function execute()
    {
        if (!$this->configHelper->isLoggedIn()) {
            $this->messageManager->addWarningMessage(__('To enter this section you need to be logged in'));
            return $this->_redirect('customer/account/login');
        }

        $this->_view->loadLayout();
        $this->_view->renderLayout();
    }
}
