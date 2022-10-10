<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2022 Improntus (http://www.improntus.com/)
 * @package Improntus_Rebill
 */

namespace Improntus\Rebill\Api\Payment;

use Magento\Framework\Api\SearchResultsInterface;

interface SearchResultInterface extends SearchResultsInterface
{
    /**
     * @return DataInterface[]
     */
    public function getItems();

    /**
     * @param DataInterface[] $items
     * @return void
     */
    public function setItems(array $items);
}
