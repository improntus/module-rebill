<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2022 Improntus (http://www.improntus.com/)
 * @package Improntus_Rebill
 */

namespace Improntus\Rebill\Model\ResourceModel\Price;

use Improntus\Rebill\Model\Entity\Price\Model;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Improntus\Rebill\Model\ResourceModel\Price as ResourceModel;

class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_model = Model::class;

    /**
     * @var string
     */
    protected $_resourceModel = ResourceModel::class;

    /**
     * @var string
     */
    protected $_itemObjectClass = Model::class;
}
