<?php

namespace Improntus\Rebill\Model\ResourceModel\Currency;

use Improntus\Rebill\Model\Entity\Currency\Model as Model;
use Improntus\Rebill\Model\ResourceModel\Currency as ResourceModel;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_eventPrefix = 'rebill_currency_collection';

    /**
     * Initialize collection model.
     */
    protected function _construct()
    {
        $this->_init(Model::class, ResourceModel::class);
    }
}
