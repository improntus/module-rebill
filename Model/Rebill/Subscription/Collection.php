<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2022 Improntus (http://www.improntus.com/)
 * @package Improntus_Rebill
 */

namespace Improntus\Rebill\Model\Rebill\Subscription;

use Exception;
use Improntus\Rebill\Helper\Config;
use Improntus\Rebill\Model\Entity\Price\Model as Price;
use Improntus\Rebill\Model\Rebill\Item;
use Improntus\Rebill\Model\Rebill\Subscription;
use Improntus\Rebill\Model\ResourceModel\Price\CollectionFactory;
use Magento\Framework\Data\Collection\EntityFactoryInterface;
use Magento\Framework\DataObject;

class Collection extends \Magento\Framework\Data\Collection
{
    /**
     * @var Subscription
     */
    protected $subscription;

    /**
     * @var ModelFactory
     */
    protected $modelFactory;

    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var Config
     */
    protected $configHelper;

    /**
     * @var Item
     */
    protected $rebillItem;

    /**
     * @param EntityFactoryInterface $entityFactory
     * @param Subscription $subscription
     * @param ModelFactory $modelFactory
     * @param CollectionFactory $collectionFactory
     * @param Config $configHelper
     * @param Item $item
     */
    public function __construct(
        EntityFactoryInterface $entityFactory,
        Subscription           $subscription,
        ModelFactory           $modelFactory,
        CollectionFactory      $collectionFactory,
        Config                 $configHelper,
        Item                   $item
    ) {
        $this->rebillItem = $item;
        $this->configHelper = $configHelper;
        $this->collectionFactory = $collectionFactory;
        $this->modelFactory = $modelFactory;
        $this->subscription = $subscription;
        $this->setItemObjectClass(Model::class);
        $this->setItems();
        parent::__construct($entityFactory);
    }

    /**
     * @return void
     */
    protected function setItems()
    {
        $items = [];
        $pricesIds = [];
        $x = 0;
        try {
            $cycleList = $this->subscription->getList('cycle')['data'] ?? [];
            $recurrentList = $this->subscription->getList('recurrent')['data'] ?? [];
            foreach ($cycleList as $item) {
                $items[$x] = $this->getNewEmptyItem()->setData($this->getItemsData($item));
                $pricesIds[$item['price']['id']] = $x;
                $x++;
            }
            foreach ($recurrentList as $item) {
                $items[$x] = $this->getNewEmptyItem()->setData($this->getItemsData($item));
                $pricesIds[$item['price']['id']] = $x;
                $x++;
            }
            /** @var \Improntus\Rebill\Model\ResourceModel\Price\Collection $prices */
            $prices = $this->collectionFactory->create();
            $prices->addFieldToFilter('rebill_price_id', ['in' => array_keys($pricesIds)]);
            /** @var Price $price */
            foreach ($prices as $price) {
                $priceId = $price->getData('rebill_price_id');
                $index = $pricesIds[$priceId];
                unset($pricesIds[$priceId]);
                $items[$index]['title'] = __(
                    '%1 x %2 - %3',
                    $items[$index]['quantity'],
                    $items[$index]['title'],
                    $this->configHelper->getFrequencyDescription(
                        null,
                        json_decode($price->getData('details'), true),
                        $items[$index]['amount']
                    )
                );
            }
            if (count($pricesIds)) {
                foreach ($items as &$item) {
                    $item->setData('cancel_id', implode('|', [
                        $item->getData('id'),
                        $item->getData('user_email')
                    ]));
                    if (isset($pricesIds[$item->getData('price')['id']])) {
                        $price = $item->getData('price');
                        if ($price) {
                            $frequency = [
                                "initialCost"       => 0,
                                "frequency"         => $price['frequency']['quantity'],
                                "frequencyType"     => $price['frequency']['quantity'],
                                "recurringPayments" => $price['repetitions'],
                            ];
                            $item->setData('title', __(
                                '%1 x %2 - %3',
                                $item->getData('quantity'),
                                $item->getData('title'),
                                $this->configHelper->getFrequencyDescription(null, $frequency, $price['amount'])
                            ));
                        }
                    }
                }
            }
        } catch (Exception $exception) {
            $items = [];
        }
        $this->_items = $items;
    }

    /**
     * @param $item
     * @return array
     */
    protected function getItemsData($item)
    {
        return [
            'id'               => $item['id'],
            'status'           => $item['status'],
            'quantity'         => $item['quantity'],
            'user_email'       => $item['userEmail'],
            'title'            => $item['title'],
            'price'            => $item['price'],
            'last_charge_date' => $item['lastChargeDate'],
            'next_charge_date' => $item['nextChargeDate'],
        ];
    }

    /**
     * @return Model|DataObject
     */
    public function getNewEmptyItem()
    {
        return $this->modelFactory->create();
    }
}
