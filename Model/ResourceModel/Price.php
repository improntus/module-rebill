<?php
namespace Improntus\Rebill\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Price extends AbstractDb
{

    protected function _construct()
    {
        $this->_init('rebill_item_price', 'entity_id');
    }
}
