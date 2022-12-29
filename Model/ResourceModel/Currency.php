<?php

namespace Improntus\Rebill\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Currency extends AbstractDb
{
    /**
     * @var string
     */
    protected $_eventPrefix = 'rebill_currency_resource_model';

    /**
     * Initialize resource model.
     */
    protected function _construct()
    {
        $this->_init('rebill_currency', 'entity_id');
        $this->_useIsObjectNew = true;
    }
}
