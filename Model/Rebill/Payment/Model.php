<?php

namespace Improntus\Rebill\Model\Rebill\Payment;

use Exception;
use Improntus\Rebill\Model\Rebill\Payment;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\DataObject;

class Model extends DataObject
{
    protected $payment;
    protected $scopeConfig;

    public function __construct(
        Payment              $payment,
        ScopeConfigInterface $scopeConfig,
        array                $data = []
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->payment = $payment;
        parent::__construct($data);
    }

    public function load($id)
    {
        try {
            $item = $this->payment->getPaymentById($id);
            $this->setData([
                'id'                  => $item['id'],
                'status'              => $item['status'],
                'amount'              => $item['amount'],
                'currency'            => $item['currency'],
                'payment_id'          => $item['paymentId'],
                'card_last4'          => $item['card']['last4'],
                'card_brand'          => $item['card']['brand'],
                'payer_email'         => $item['payer']['email'],
                'payer_firstname'     => $item['payer']['firstName'],
                'payer_lastname'      => $item['payer']['lastName'],
                'description'         => $item['description'],
                'gateway_type'        => $item['gateway']['type'],
                'gateway_description' => $item['gateway']['description'],
            ]);
        } catch (Exception $exception) {

        }
    }
}
