<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2022 Improntus (http://www.improntus.com/)
 * @package Improntus_Rebill
 */

namespace Improntus\Rebill\Controller\Payment;

use Exception;
use Improntus\Rebill\Helper\Config;
use Improntus\Rebill\Model\Rebill\Subscription;
use Improntus\Rebill\Model\Sales\Invoice;
use Improntus\Rebill\Model\SubscriptionFactory;
use Improntus\Rebill\Model\Webhook;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Sales\Model\OrderRepository;

class Success extends Action
{
    /**
     * @var Session
     */
    protected $session;

    /**
     * @var Subscription
     */
    protected $subscription;

    /**
     * @var SubscriptionFactory
     */
    protected $subscriptionFactory;

    /**
     * @var OrderRepository
     */
    protected $orderRepository;

    /**
     * @var Invoice
     */
    protected $invoice;

    /**
     * @var Config
     */
    protected $configHelper;

    /**
     * @var OrderSender
     */
    protected $orderSender;

    /**
     * @var Webhook
     */
    protected $webhook;

    /**
     * @param Context $context
     * @param Session $session
     * @param Subscription $subscription
     * @param SubscriptionFactory $subscriptionFactory
     * @param OrderRepository $orderRepository
     * @param Invoice $invoice
     * @param Config $configHelper
     * @param OrderSender $orderSender
     * @param Webhook $webhook
     */
    public function __construct(
        Context             $context,
        Session             $session,
        Subscription        $subscription,
        SubscriptionFactory $subscriptionFactory,
        OrderRepository     $orderRepository,
        Invoice             $invoice,
        Config              $configHelper,
        OrderSender         $orderSender,
        Webhook             $webhook
    ) {
        $this->webhook = $webhook;
        $this->configHelper = $configHelper;
        $this->invoice = $invoice;
        $this->orderRepository = $orderRepository;
        $this->session = $session;
        $this->subscription = $subscription;
        $this->subscriptionFactory = $subscriptionFactory;
        $this->orderSender = $orderSender;

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
        }
        $this->_redirect('checkout/onepage/success');
    }
}
