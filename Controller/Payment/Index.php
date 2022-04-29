<?php

namespace Improntus\Rebill\Controller\Payment;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Checkout\Model\Session as CheckoutSession;

/**
 * Class Index
 *
 * @author Improntus <https://www.improntus.com>
 * @package Improntus\Rebill\Controller\Payment
 */
class Index extends Action
{
    /**
     * @var PageFactory
     */
    protected $_resultPageFactory;

    /**
     * @var OrderRepositoryInterface
     */
    protected $_orderRepository;

    /**
     * @var ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var CheckoutSession
     */
    protected $_checkoutSession;

    /**
     * Index constructor.
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param OrderRepositoryInterface $orderRepositoryInterface
     * @param ScopeConfigInterface $scopeConfigInterface
     * @param CheckoutSession $checkoutSession
     */
    public function __construct
    (
        Context $context,
        PageFactory $resultPageFactory,
        OrderRepositoryInterface $orderRepositoryInterface,
        ScopeConfigInterface $scopeConfigInterface,
        CheckoutSession $checkoutSession
    )
    {
        $this->_resultPageFactory   = $resultPageFactory;
        $this->_orderRepository     = $orderRepositoryInterface;
        $this->_scopeConfig         = $scopeConfigInterface;
        $this->_checkoutSession     = $checkoutSession;

        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Redirect|\Magento\Framework\Controller\ResultInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute()
    {
        $paymentToken = $this->_checkoutSession->getPaymentTokenSmartFields();
        $incrementId = $this->_checkoutSession->getLastRealOrderId();
        $installments = $this->_checkoutSession->getSmartFieldsInstallments();
        $resultRedirect = $this->resultRedirectFactory->create();
        $redirectUrl = 'checkout/onepage/failure';

//        die('aaa');
//        $processPayment = $this->_payment->processPayment($incrementId,$paymentToken,$installments);

//        if($processPayment == 'success') {
            $redirectUrl = 'checkout/onepage/success';
//        }elseif($processPayment == 'pending') {
//            $redirectUrl = 'checkout/onepage/pending';
//        }
//
        $resultRedirect->setUrl($this->_url->getUrl($redirectUrl));

        return $resultRedirect;
    }
}
