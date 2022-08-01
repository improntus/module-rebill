<?php

namespace Improntus\Rebill\Model\Rebill\Payment;

use Improntus\Rebill\Model\Rebill\Payment;
use Magento\Framework\Data\Collection\EntityFactoryInterface;

class Collection extends \Magento\Framework\Data\Collection
{
    protected $payment;
    protected $modelFactory;

    public function __construct(
        EntityFactoryInterface $entityFactory,
        Payment                $payment,
        ModelFactory           $modelFactory
    ) {
        $this->modelFactory = $modelFactory;
        $this->payment = $payment;
        $this->setItemObjectClass(Model::class);
        $this->setItems();
        parent::__construct($entityFactory);
    }

    public function getNewEmptyItem()
    {
        return $this->modelFactory->create();
    }

    protected function setItems()
    {
        $paymentList = $this->payment->getList();
        $items = [];
        foreach ($paymentList as $item) {
            try {
                $items[] = $this->getNewEmptyItem()->setData([
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
            } catch (\Exception $exception) {

            }
        }
        $this->_items = $items;
    }
}
