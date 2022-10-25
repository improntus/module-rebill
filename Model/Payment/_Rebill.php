<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2022 Improntus (http://www.improntus.com/)
 * @package Improntus_Rebill
 */

namespace Improntus\Rebill\Model\Payment;

use Improntus\Rebill\Helper\Config;
use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\Exception\LocalizedException;
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
use Magento\Framework\UrlInterface;

class _Rebill extends Cc implements GatewayInterface
{
    const CODE = 'improntus_rebill';

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
    protected $_code = 'improntus_rebill';

    /**
     * @var bool
     */
    protected $_isGateway = true;

    /**
     * @var bool
     */
    protected $_canCapture = true;

    /**
     * @var bool
     */
    protected $_canCapturePartial = false;

    /**
     * @var bool
     */
    protected $_canRefund = false;

    /**
     * @var int
     */
    protected $_minOrderTotal = 0;

    /**
     * @var Config
     */
    protected $configHelper;

    /**
     * @var UrlInterface
     */
    protected $_urlBuilder;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param ExtensionAttributesFactory $extensionFactory
     * @param AttributeValueFactory $customAttributeFactory
     * @param Data $paymentData
     * @param ScopeConfigInterface $scopeConfig
     * @param Logger $logger
     * @param ModuleListInterface $moduleList
     * @param TimezoneInterface $localeDate
     * @param Config $configHelper
     * @param UrlInterface $urlBuilder
     * @param array $data
     */
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
        UrlInterface               $urlBuilder,
        array                      $data = []
    ) {
        $this->configHelper = $configHelper;
        $this->_urlBuilder = $urlBuilder;

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

    /**
     * A flag to set that there will be redirect to third party after confirmation
     *
     * @return string
     */
    public function getOrderPlaceRedirectUrl()
    {
        return $this->_urlBuilder->getUrl(self::ACTION_URL);
    }

    /**
     * @param CartInterface|null $quote
     * @return bool
     */
    public function isAvailable(CartInterface $quote = null)
    {
        if ($this->configHelper->isEnabled() && $quote && $quote->getBaseGrandTotal() < $this->_minOrderTotal) {
            return false;
        }
        return true;
    }

    /**
     * @param DataObject $request
     * @param ConfigInterface $config
     * @return DataObject|void
     */
    public function postRequest(DataObject $request, ConfigInterface $config)
    {
        $this->configHelper->logInfo('postRequest');
        $this->configHelper->logInfo(json_encode($request->getData()));
        // TODO: Implement postRequest() method.
    }

    /**
     * @return $this|Rebill
     * @throws LocalizedException
     */
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