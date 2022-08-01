<?php

namespace Improntus\Rebill\Model\Payment;

use Improntus\Rebill\Helper\Config;
use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Model\Context;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Payment\Block\Form;
use Improntus\Rebill\Block\Sales\Order\Rebill as RebillBlock;
use Magento\Payment\Helper\Data;
use Magento\Payment\Model\InfoInterface;
use Magento\Payment\Model\Method\Cc;
use Magento\Payment\Model\Method\ConfigInterface;
use Magento\Payment\Model\Method\Logger;
use Magento\Payment\Model\Method\Online\GatewayInterface;
use Magento\Quote\Api\Data\CartInterface;

class Rebill extends Cc implements GatewayInterface
{
    const CODE = 'improntus_rebill';

    /**
     * define URL to go when an order is placed
     */
    const ACTION_URL = 'rebill/payment/transaction';

    /**
     * @var string
     */
    protected $_formBlockType = Form::class;

    /**
     * @var string
     */
    protected $_infoBlockType = RebillBlock::class;

    /**
     * @var string
     */
    protected $_template = 'Improntus_Rebill::info/rebill.phtml';

    /**
     * @var string
     */
    protected $_code = self::CODE;
    protected $_isGateway = true;
    protected $_canCapture = false;
    protected $_canCapturePartial = false;
    protected $_canRefund = false;
    protected $_minOrderTotal = 0;
    protected $configHelper;
    protected $_supportedCurrencyCodes = [
        'USD',
        'ARS',
        'CLP',
        'MXN',
        'BRL',
        'COP',
        'PEN',
        'VEB',
        'VEF'
    ];

    public function __construct(
        Context                    $context,
        Registry                   $registry,
        ExtensionAttributesFactory $extensionFactory,
        AttributeValueFactory      $customAttributeFactory,
        Data                       $paymentData,
        ScopeConfigInterface       $scopeConfig,
        Logger                     $logger,
        ModuleListInterface        $moduleList,
        TimezoneInterface          $localeDate,
        Config                     $configHelper,
        array                      $data = array()
    ) {
        $this->configHelper = $configHelper;
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            $moduleList,
            $localeDate,
            null,
            null,
            $data
        );
    }

    public function canUseForCurrency($currencyCode)
    {
        if (!in_array($currencyCode, $this->_supportedCurrencyCodes)) {
            return false;
        }
        return true;
    }

    public function isAvailable(CartInterface $quote = null)
    {
        if ($this->configHelper->isEnabled() && $quote && $quote->getBaseGrandTotal() < $this->_minOrderTotal) {
            return false;
        }
        return true;
    }

    public function postRequest(DataObject $request, ConfigInterface $config)
    {
        $this->configHelper->logInfo('postRequest');
        $this->configHelper->logInfo(json_encode($request->getData()));
        // TODO: Implement postRequest() method.
    }

    public function validate()
    {
        $info = $this->getInfoInstance();
        $this->configHelper->logInfo('validate Additional Information');
        $this->configHelper->logInfo(json_encode($info->getAdditionalInformation()));
        $this->configHelper->logInfo('validate Info');
        $this->configHelper->logInfo(json_encode($info->getData()));

        return $this;
    }
}
