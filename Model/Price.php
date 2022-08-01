<?php

namespace Improntus\Rebill\Model;

use Magento\Framework\Model\AbstractModel;

class Price extends AbstractModel
{
    protected function _construct()
    {
        $this->_init(ResourceModel\Price::class);
    }
}
