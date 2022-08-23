<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2022 Improntus (http://www.improntus.com/)
 * @package Improntus_Rebill
 */

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
        try {
            $this->transaction->prepareTransaction();
        } catch (Exception $exception) {
            $this->messageManager->addErrorMessage($exception->getMessage());
            $this->_redirect('checkout/cart/index');
            return;
        }
        $this->_view->loadLayout();
        $this->_view->renderLayout();
    }
}
