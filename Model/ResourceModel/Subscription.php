<?php
namespace Improntus\Rebill\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Subscription extends AbstractDb
{

    protected function _construct()
    {
        $this->_init('rebill_subscription', 'entity_id');
    }
}
