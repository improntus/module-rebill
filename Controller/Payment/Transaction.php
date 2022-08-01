<?php

namespace Improntus\Rebill\Controller\Payment;

use Exception;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Improntus\Rebill\Model\Payment\Transaction as TransactionModel;

class Transaction extends Action
{
    /**
     * @var TransactionModel
     */
    protected $transaction;

    /**
     * @param Context $context
     * @param TransactionModel $transaction
     */
    public function __construct(
        Context          $context,
        TransactionModel $transaction
    ) {
        $this->transaction = $transaction;
        parent::__construct($context);
    }

    /**
     * @return void
     * @throws Exception
     */
    public function execute()
    {
        $result = $this->transaction->prepareTransaction();
        if (!$result) {
            $this->messageManager->addErrorMessage(__('Can\'t find any order to pay with rebill.'));
            $this->_redirect('checkout/cart/index');
            return;
        }
        $this->_view->loadLayout();
        $this->_view->renderLayout();
    }
}
