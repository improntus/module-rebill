<?php

namespace Improntus\Rebill\Model;

use Magento\Framework\Registry;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\ResourceModel\AbstractResource;

class Subscription extends AbstractModel
{
    /**
     * @var PriceFactory
     */
    protected $priceFactory;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param PriceFactory $priceFactory
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context          $context,
        Registry         $registry,
        PriceFactory     $priceFactory,
        AbstractResource $resource = null,
        AbstractDb       $resourceCollection = null,
        array            $data = []
    ) {
        $this->priceFactory = $priceFactory;
        parent::__construct(
            $context,
            $registry,
            $resource,
            $resourceCollection,
            $data
        );
    }

    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init(ResourceModel\Subscription::class);
    }

    /**
     * @return Price|null
     */
    public function getItemPrice()
    {
        if (!$this->getId()) {
            return null;
        }
        $price = $this->priceFactory->create();
        $price->load($this->getData('price_id'), 'rebill_price_id');
        return $price->getId() ? $price : null;
    }
}
