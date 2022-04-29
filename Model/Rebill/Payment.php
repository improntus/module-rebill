<?php

namespace Improntus\Rebill\Model\Rebill;

use Magento\Checkout\Model\Session;
use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\UrlInterface;
use Magento\Payment\Block\Form;
use Magento\Payment\Helper\Data;
use Magento\Payment\Model\Method\AbstractMethod;
use Magento\Payment\Model\Method\Logger;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Improntus\Rebill\Helper\Data as RebillHelper;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Improntus\PriceDecimals\Helper\Data as PrecisionHelper;
use Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface;
use Improntus\Rebill\Block\Sales\Order\Rebill;

/**
 * Class Payment
 *
 * @author Improntus <https://www.improntus.com>
 * @package Improntus\Rebill\Model
 */
class Payment extends AbstractMethod
{
    const CODE = 'improntus_rebill';

    /**
     * define URL to go when an order is placed
     */
    const ACTION_URL = 'rebill/payment';

    /**
     * @var string
     */
    protected $_formBlockType = Form::class;

    /**
     * @var string
     */
    protected $_infoBlockType = Rebill::class;

    /**
     * @var string
     */
    protected $_template = 'Improntus_Rebill::info/rebill.phtml';

    /**
     * @var string
     */
    protected $_code = self::CODE;

    /**
     * @var bool
     */
    protected $_canCapture = true;

    /**
     * @var bool
     */
    protected $_isGateway = true;

    /**
     * Payment Method feature
     *
     * @var bool
     */
    protected $_canRefund = true;

    /**
     * Payment Method feature
     *
     * @var bool
     */
    protected $_canRefundInvoicePartial = true;

    /**
     * @var Session
     */
    protected $_checkoutSession;

    /**
     * @var bool
     */
    protected $_isOffline = false;

    /**
     * @var bool
     */
    protected $_canOrder = true;

    /**
     * @var BuilderInterface
     */
    protected $transactionBuilder;

    /**
     * Availability option
     *
     * @var bool
     */
    protected $_isInitializeNeeded = false;

    /**
     * @var UrlInterface
     */
    protected $_urlBuilder;

    /**
     * @var OrderInterface
     */
    protected $_order;

    /**
     * @var OrderSender
     */
    protected $_orderSender;

    /**
     * @var PrecisionHelper
     */
    protected $_precisionHelper;

    /**
     * Payment constructor.
     * @param Context $context
     * @param Registry $registry
     * @param ExtensionAttributesFactory $extensionFactory
     * @param AttributeValueFactory $customAttributeFactory
     * @param Data $paymentData
     * @param ScopeConfigInterface $scopeConfig
     * @param Logger $logger
     * @param ModuleListInterface $moduleList
     * @param TimezoneInterface $localeDate
     * @param Session $checkoutSession
     * @param BuilderInterface $transactionBuilder
     * @param UrlInterface $urlBuilder
     * @param RebillHelper $RebillHelper
     * @param OrderInterface $orderInterface
     * @param OrderSender $orderSender
     * @param PrecisionHelper $precisionHelper
     * @param Webservice $webservice
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ExtensionAttributesFactory $extensionFactory,
        AttributeValueFactory $customAttributeFactory,
        Data $paymentData,
        ScopeConfigInterface $scopeConfig,
        Logger $logger,
        ModuleListInterface $moduleList,
        TimezoneInterface $localeDate,
        Session $checkoutSession,
        BuilderInterface $transactionBuilder,
        UrlInterface $urlBuilder,
        OrderInterface $orderInterface,
        OrderSender $orderSender,
        PrecisionHelper $precisionHelper,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    )
    {
        $this->_checkoutSession     = $checkoutSession;
        $this->transactionBuilder   = $transactionBuilder;
        $this->_urlBuilder          = $urlBuilder;
        $this->_order               = $orderInterface;
        $this->_orderSender         = $orderSender;
        $this->_precisionHelper     = $precisionHelper;

        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            $resource,
            $resourceCollection,
            $data
        );
    }

    /**
     * @return string
     */
    public function getActionUrl()
    {
        return $this->_urlBuilder->getUrl(self::ACTION_URL);
    }

    /**
     * @param OrderPaymentInterface $payment
     * @param array $comments
     * @return Invoice
     * @throws LocalizedException
     */
    protected function invoice(OrderPaymentInterface $payment, array $comments = [])
    {
        /** @var Invoice $invoice */
        $invoice = $payment->getOrder()->prepareInvoice();

        $invoice->register();
        if ($payment->getMethodInstance()->canCapture()) {
            $invoice->capture();
        }

        $payment->getOrder()->addRelatedObject($invoice);

        foreach ($comments as $comment)
        {
            $invoice->addComment(
                $comment,
                true,
                true
            );
        }

        return $invoice;
    }

    /**
     * Determine method availability based on quote amount and config data
     *
     * @param CartInterface|null $quote
     * @return bool
     */
    public function isAvailable(CartInterface $quote = null)
    {
        return true;
    }

    /**
     * @param string $incrementId
     * @param string $paymentToken
     * @param string $installments
     * @return bool|string
     * @throws LocalizedException
     */
    public function processPayment($incrementId,$paymentToken,$installments)
    {
        $order = $this->_order->loadByIncrementId($incrementId);
        $orderPayment = $order->getPayment();

        $grandTotal = (float)round($order->getGrandTotal(),$this->_precisionHelper->getDefaultPrecision());

        $installments = explode('-',$installments);
        $installments = array_pop($installments);

//        $payment = $this->_webservice->sendPayment([
//            'amount'=> $grandTotal,
//            'currency' => $this->_rebillHelper->getCurrency(),
//            'country'=> $this->_rebillHelper->getCountry(),
//            'payment_method_id' => 'CARD',
//            'payment_method_flow' => 'DIRECT',
//            'payer'=> [
//                'name' => $order->getCustomerName(),
//                'email' => $order->getCustomerEmail(),
//                'document' => $this->_checkoutSession->getDocumentSmartFields(), //'42181226',////$order->getCustomerTaxvat(),
//                'user_reference' => $order->getIncrementId(),
//                'address'=> [
//                    'state'  => '',
//                    'city' => '',
//                    'zip_code' => '',
//                    'street' => '',
//                    'number' => ''
//                ]
//            ],
//            'card'=> [
//                'installments' => $installments,
//                'token'=> $paymentToken
//            ],
//            'order_id'=> $order->getIncrementId(),
//            'notification_url' => $this->_urlBuilder->getUrl('rebill/payment/ipn')
//        ]);

//        if(isset($payment->status_code))
//        {
//            if($payment->status_code == 200)
//            {
//                foreach ((array)$payment as $key => $value)
//                {
//                    if($key == 'card')
//                    {
//                        $orderPayment->setAdditionalInformation($key,(array)$value);
//                    }
//                    else
//                    {
//                        $orderPayment->setAdditionalInformation($key,$value);
//                    }
//                }
//
//                $orderPayment->setTransactionId($order->getIncrementId());
//
//                $transaction = $this->transactionBuilder
//                    ->setPayment($orderPayment)
//                    ->setOrder($order)
//                    ->setTransactionId($orderPayment->getTransactionId())
//                    ->build(Transaction::TYPE_AUTH);
//
//                $statusMessage  = [
//                    __('Payment status'). ': ' . __($payment->status_detail),
//                    __('Payment id: "%1".', $payment->id)
//                ];
//
//                foreach ($statusMessage as $_message)
//                {
//                    $orderPayment->addTransactionCommentsToOrder($transaction, $_message);
//                }
//
//                $this->invoice($orderPayment,$statusMessage);
//                $order->setStatus('processing');
//                $order->save();
//
//                return 'success';
//            }elseif($payment->status_code == 100){
//                $statusDescription = $this->_statusCode->getStatusDescription($payment->status_code);
//
//                $orderPayment->setAdditionalInformation('pending_card',$statusDescription);
//                $this->_checkoutSession->setErrorMessage($statusDescription);
//
//                return 'pending';
//            }else{
//                $statusDescription = $this->_statusCode->getStatusDescription($payment->status_code);
//
//                $orderPayment->setAdditionalInformation('error_card',$statusDescription);
//                $this->_checkoutSession->setErrorMessage($statusDescription);
//
//                $order->setStatus('cancelled');
//                $order->cancel();
//                $order->save();
//
//                return 'failure';
//            }
//        }
//        else if(isset($payment->code))
//        {
//            $errorMsg = '';
//
//            foreach ($payment as $key=>$value)
//            {
//                $orderPayment->setAdditionalInformation('error_'.$key,$value);
//
//                $errorMsg .= "<p>$key: $value</p><br>";
//            }
//
//            $this->_checkoutSession->setErrorMessage($errorMsg);
//
//            $order->setStatus('cancelled');
//            $order->cancel();
//            $order->save();
//
//            return false;
//        }
    }
}
