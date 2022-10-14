<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2022 Improntus (http://www.improntus.com/)
 * @package Improntus_Rebill
 */

namespace Improntus\Rebill\Model\ResourceModel\SubscriptionShipment;

use Improntus\Rebill\Model\Entity\SubscriptionShipment\Model;
use Improntus\Rebill\Model\ResourceModel\SubscriptionShipment as ResourceModel;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

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
