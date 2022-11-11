<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Improntus\Rebill\Model\Carrier;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Registry;
use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory;
use Magento\Quote\Model\Quote\Address\RateResult\MethodFactory;
use Magento\Shipping\Model\Carrier\AbstractCarrier;
use Magento\Shipping\Model\Carrier\CarrierInterface;
use Magento\Shipping\Model\Rate\Result;
use Magento\Shipping\Model\Rate\ResultFactory;
use Psr\Log\LoggerInterface;

class Rebill extends AbstractCarrier implements CarrierInterface
{

    protected $_code = 'rebill';

    protected $_isFixed = true;

    /**
     * @var ResultFactory
     */
    protected $_rateResultFactory;
    /**
     * @var MethodFactory
     */
    protected $_rateMethodFactory;

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param ErrorFactory $rateErrorFactory
     * @param LoggerInterface $logger
     * @param ResultFactory $rateResultFactory
     * @param MethodFactory $rateMethodFactory
     * @param Registry $registry
     * @param array $data
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        ErrorFactory         $rateErrorFactory,
        LoggerInterface      $logger,
        ResultFactory        $rateResultFactory,
        MethodFactory        $rateMethodFactory,
        Registry             $registry,
        array                $data = []
    ) {
        $this->registry = $registry;
        $this->_rateResultFactory = $rateResultFactory;
        $this->_rateMethodFactory = $rateMethodFactory;
        parent::__construct($scopeConfig, $rateErrorFactory, $logger, $data);
    }

    /**
     * @param RateRequest $request
     * @return bool|DataObject|Result|null
     */
    public function collectRates(RateRequest $request)
    {
        $rebillReorderData = $this->registry->registry('rebill_reorder_data');
        if (!$rebillReorderData) {
            return null;
        }
        $shippingPrice = $rebillReorderData['shipping_costs']['shipping_amount'];
        $result = $this->_rateResultFactory->create();
        $method = $this->_rateMethodFactory->create();
        $method->setCarrier($this->_code);
        $method->setCarrierTitle(__('Shipping Method'));
        $method->setMethod($this->_code);
        $method->setMethodTitle($rebillReorderData['shipping_description']);
        $method->setPrice($shippingPrice);
        $method->setCost($shippingPrice);
        $result->append($method);

        return $result;
    }

    /**
     * getAllowedMethods
     *
     * @return array
     */
    public
    function getAllowedMethods()
    {
        return [$this->_code => $this->getConfigData('name')];
    }
}
