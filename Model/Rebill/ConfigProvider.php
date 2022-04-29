<?php

namespace Improntus\Rebill\Model\Rebill;

use Exception;
use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Helper\Data as PaymentHelper;
use Improntus\Rebill\Helper\Data as SmartFieldsHelper;

use \Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Payment\Model\MethodInterface;
use \Magento\Quote\Api\CartRepositoryInterface;
use \Magento\Catalog\Api\CategoryRepositoryInterface;
use \Magento\Store\Model\StoreManagerInterface;


/**
 * Class ConfigProvider
 *
 * @author Improntus <https://www.improntus.com>
 * @package Improntus\Rebill\Model
 */
class ConfigProvider implements ConfigProviderInterface
{
    /**
     * @var string
     */
    protected $methodCode = Payment::CODE;

    /**
     * @var MethodInterface
     */
    protected $method;

    /**
     * @var CheckoutSession
     */
    protected $_checkoutSession;

    /**
     * @var CartRepositoryInterface
     */
    protected $_cart;

    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * ConfigProvider constructor.
     *
     * @param PaymentHelper           $paymentHelper
     * @param CheckoutSession         $checkoutSession
     * @param CartRepositoryInterface $cart
     * @param StoreManagerInterface   $storeManager
     *
     * @throws LocalizedException
     */
    public function __construct(
        PaymentHelper $paymentHelper,
        CheckoutSession $checkoutSession,
        CartRepositoryInterface $cart,
        StoreManagerInterface $storeManager
    )
    {
        $this->method = $paymentHelper->getMethodInstance($this->methodCode);

        $this->_checkoutSession = $checkoutSession;
        $this->_cart = $cart;
        $this->_storeManager = $storeManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig()
    {
        return $this->method->isAvailable() ? [
            'payment' => [
                $this->methodCode =>[
                    'actionUrl' =>  $this->method->getActionUrl(),
                    'currency'  =>  '',
                    'country' => ''
                ]
            ],
        ] : [];
    }
}
