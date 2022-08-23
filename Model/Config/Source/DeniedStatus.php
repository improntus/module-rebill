<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2022 Improntus (http://www.improntus.com/)
 * @package Improntus_Rebill
 */

namespace Improntus\Rebill\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;
use Magento\Sales\Model\ResourceModel\Order\Status\CollectionFactory;

class DeniedStatus implements ArrayInterface
{
    /**
     * @var CollectionFactory
     */
    protected $statusCollectionFactory;

    /**
     * @param CollectionFactory $statusCollectionFactory
     */
    public function __construct(
        CollectionFactory $statusCollectionFactory
    ) {
        $this->statusCollectionFactory = $statusCollectionFactory;
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {
        $collection = $this->statusCollectionFactory->create();
        $collection->addStateFilter('canceled');
        return $collection->toOptionArray();
    }
}
