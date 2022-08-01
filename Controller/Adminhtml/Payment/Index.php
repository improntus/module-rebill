<?php

namespace Improntus\Rebill\Controller\Adminhtml\Payment;

use Magento\Backend\App\Action;

class Index extends Action
{
    public function execute()
    {
        $this->_view->loadLayout();
        $this->_view->renderLayout();
    }
}
