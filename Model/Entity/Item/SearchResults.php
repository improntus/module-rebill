<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2022 Improntus (http://www.improntus.com/)
 * @package Improntus_Rebill
 */

namespace Improntus\Rebill\Model\Entity\Item;

use Improntus\Rebill\Api\Item\SearchResultInterface;
use Improntus\Rebill\Api\Item\DataInterface;

class SearchResults extends \Magento\Framework\Api\SearchResults implements SearchResultInterface
{
    /**
     * @return DataInterface[]
     */
    public function getItems()
    {
        return $this->_get(self::KEY_ITEMS) === null ? [] : $this->_get(self::KEY_ITEMS);
    }

    /**
     * @return DataInterface|null
     */
    public function getFirstItem()
    {
        if ($this->getTotalCount() > 0) {
            return $this->getItems()[array_key_first($this->getItems())];
        }
        return null;
    }

    /**
     * @param DataInterface[] $items
     * @return $this
     */
    public function setItems(array $items)
    {
        return $this->setData(self::KEY_ITEMS, $items);
    }
}
