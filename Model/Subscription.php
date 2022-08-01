<?php

namespace Improntus\Rebill\Model;

use Magento\Framework\Model\AbstractModel;

class Subscription extends AbstractModel
{
    protected function _construct()
    {
        $this->_init(ResourceModel\Subscription::class);
    }
}
