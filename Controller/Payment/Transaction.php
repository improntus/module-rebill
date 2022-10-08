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
            $result = $this->transaction->prepareTransaction();
        } catch (Exception $exception) {
            $this->messageManager->addErrorMessage($exception->getMessage());
            $this->_redirect('checkout/cart/index');
            return;
        }
        $this->_view->loadLayout();
        /** @var \Improntus\Rebill\Block\Payment\Transaction $rebillBlock */
        $rebillBlock = $this->_view->getLayout()->getBlock('rebill_payment_transaction');
        $rebillBlock->setOrder($result['order']);
        $rebillBlock->setPrices($result['rebill_details']);
        $this->_view->renderLayout();
    }
}
