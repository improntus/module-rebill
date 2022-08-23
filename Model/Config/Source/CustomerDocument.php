<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2022 Improntus (http://www.improntus.com/)
 * @package Improntus_Rebill
 */

namespace Improntus\Rebill\Model\Config\Source;

use Magento\Customer\Model\Attribute;
use Magento\Customer\Model\ResourceModel\Address\Attribute\CollectionFactory;
use Magento\Customer\Model\ResourceModel\Address\Attribute\Collection;
use Magento\Framework\Option\ArrayInterface;

class CustomerDocument implements ArrayInterface
{
    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        CollectionFactory $collectionFactory
    ) {
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {
        /** @var Collection $collection */
        $collection = $this->collectionFactory->create();
        $collection->addVisibleFilter();
        $collection->addExcludeHiddenFrontendFilter();
        $options = [];
        $excludeAttributes = [
            'firstname',
            'lastname',
            'prefix',
            'middlename',
            'suffix',
            'company',
            'street',
            'city',
            'country_id',
            'region',
            'region_id',
            'postcode',
            'telephone',
            'fax',
            'vat_is_valid',
            'vat_request_id',
            'vat_request_date',
            'vat_request_success',
        ];
        /** @var Attribute $attribute */
        foreach ($collection as $attribute) {
            if (in_array($attribute->getAttributeCode(), $excludeAttributes)) {
                continue;
            }
            $options[] = ['value' => $attribute->getAttributeCode(), 'label' => $attribute->getDefaultFrontendLabel()];
        }
        return $options;
    }
}
