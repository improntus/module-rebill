<?php
namespace Improntus\Rebill\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Item extends AbstractDb
{

    protected function _construct()
    {
        $this->_init('rebill_item', 'entity_id');
    }
}
