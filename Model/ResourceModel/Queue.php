<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2022 Improntus (http://www.improntus.com/)
 * @package Improntus_Rebill
 */

namespace Improntus\Rebill\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Queue extends AbstractDb
{
    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init('rebill_queue', 'entity_id');
    }
}
