<?php

namespace Improntus\Rebill\Model\Api\SearchCriteria;

use Magento\Framework\Api\SearchCriteria\CollectionProcessor\FilterProcessor;
use Magento\Framework\Api\SearchCriteria\CollectionProcessor\PaginationProcessor;
use Magento\Framework\Api\SearchCriteria\CollectionProcessor\SortingProcessor;

class CollectionProcessor extends \Magento\Framework\Api\SearchCriteria\CollectionProcessor
{
    /**
     * @param FilterProcessor $filterProcessor
     * @param SortingProcessor $sortingProcessor
     * @param PaginationProcessor $paginationProcessor
     */
    public function __construct(
        FilterProcessor     $filterProcessor,
        SortingProcessor    $sortingProcessor,
        PaginationProcessor $paginationProcessor
    ) {
        parent::__construct([
            'filters'    => $filterProcessor,
            'sorting'    => $sortingProcessor,
            'pagination' => $paginationProcessor,
        ]);
    }
}
