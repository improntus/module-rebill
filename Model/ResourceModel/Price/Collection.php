<?php
namespace Improntus\Rebill\Model\ResourceModel\Price;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Improntus\Rebill\Model\Price as Model;
use Improntus\Rebill\Model\ResourceModel\Price as ResourceModel;

class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init(Model::class, ResourceModel::class);
    }
}
