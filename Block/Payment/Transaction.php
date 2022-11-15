<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2022 Improntus (http://www.improntus.com/)
 * @package Improntus_Rebill
 */

namespace Improntus\Rebill\Block\Payment;

use Improntus\Rebill\Helper\Config;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;
use Magento\Sales\Model\Order;
use Improntus\Rebill\Model\Config\Source\CustomerDocumentType;

class Transaction extends Template
{
    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var CustomerDocumentType
     */
    protected $customerDocumentType;

    /**
     * @var Order
     */
    protected $order;

    /**
     * @var array
     */
    protected $prices;

    /**
     * @var Config
     */
    protected $configHelper;

    /**
     * @param Template\Context $context
     * @param Registry $registry
     * @param CustomerDocumentType $customerDocumentType
     * @param Config $configHelper
     * @param array $data
     */
    public function __construct(
        Template\Context     $context,
        Registry             $registry,
        CustomerDocumentType $customerDocumentType,
        Config               $configHelper,
        array                $data = []
    ) {
        parent::__construct($context, $data);
        $this->configHelper = $configHelper;
        $this->registry = $registry;
        $this->customerDocumentType = $customerDocumentType;
    }

    /**
     * @return array
     */
    public function getRebillTransaction()
    {
        $order = $this->getOrder();
        $billingAddress = $order->getBillingAddress();
        $transaction = $this->getPrices();
        $documentTypes = $this->getDocumentTypes();
        $identification = [];
        if ($documentTypes) {
            $identification['type'] = $documentTypes[0];
        }

        $apiUrl = 'https://api.rebill.to/v2';
        return [
            'component' => 'rebill',
            'urlConfirmation' => $this->getUrl('rebill/payment/success', ['order_id' => $order->getId()]),
            'initOptions' => [
                'organization_id' => $this->configHelper->getApiUuid(),
                'api_url' => $apiUrl,
            ],
            'rebillOptions' => [
                'cardHolder' => [
                    'name' => $billingAddress->getFirstname() . ' ' . $billingAddress->getLastname(),
                    'identification' => $identification,
                ],
                'customer' => [
                    'firstName' => $order->getCustomerFirstname(),
                    'lastName' => $order->getCustomerLastname(),
                    'email' => $order->getCustomerEmail(),
                    'phone' => [
                        'countryCode' => '00',
                        'areaCode' => '00',
                        'phoneNumber' => $billingAddress->getTelephone(),
                    ],
                    'address' => [
                        'street' => $billingAddress->getStreet()[0],
                        'number' => $billingAddress->getStreet()[1] ?? 'none',
                        'floor' => $billingAddress->getStreet()[2] ?? 'none',
                        'apt' => $billingAddress->getStreet()[3] ?? 'none',
                        'city' => $billingAddress->getCity(),
                        'state' => $billingAddress->getRegion(),
                        'zipCode' => $billingAddress->getPostcode() ?? '',
                        'country' => $billingAddress->getCountryId(),
                        'description' => "Billing Address",
                    ],
                ],
                'transaction' => [
                    'prices' => $transaction,
                ],
            ],
            'documentTypes' => $documentTypes,
        ];
    }

    /**
     * @return Order
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * @param Order $order
     * @return $this
     */
    public function setOrder(Order $order)
    {
        $this->order = $order;
        return $this;
    }

    /**
     * @return array
     */
    public function getPrices()
    {
        return $this->prices;
    }

    /**
     * @param array $prices
     * @return $this
     */
    public function setPrices(array $prices)
    {
        $this->prices = $prices;
        return $this;
    }

    /**
     * @return array
     */
    public function getDocumentTypes()
    {
        return array_map(function ($type) {
            return $type['value'];
        }, $this->customerDocumentType->toOptionArray());
    }
}
