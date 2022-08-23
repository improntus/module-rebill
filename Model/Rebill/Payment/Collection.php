<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2022 Improntus (http://www.improntus.com/)
 * @package Improntus_Rebill
 */

namespace Improntus\Rebill\Model\Rebill\Payment;

use Exception;
use Magento\Framework\DataObject;
use Improntus\Rebill\Model\Rebill\Payment;
use Magento\Framework\Data\Collection\EntityFactoryInterface;

class Collection extends \Magento\Framework\Data\Collection
{
    /**
     * @var Payment
     */
    protected $payment;

    /**
     * @var ModelFactory
     */
    protected $modelFactory;

    /**
     * @param EntityFactoryInterface $entityFactory
     * @param Payment $payment
     * @param ModelFactory $modelFactory
     * @throws Exception
     */
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

    /**
     * @return Model|DataObject
     */
    public function getNewEmptyItem()
    {
        return $this->modelFactory->create();
    }

    /**
     * @return void
     * @throws Exception
     */
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
            } catch (Exception $exception) {}
        }
        $this->_items = $items;
    }
}
