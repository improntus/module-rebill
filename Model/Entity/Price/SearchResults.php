<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2022 Improntus (http://www.improntus.com/)
 * @package Improntus_Rebill
 */

namespace Improntus\Rebill\Model\Entity\Price;

use Improntus\Rebill\Api\Price\SearchResultInterface;
use Improntus\Rebill\Api\Price\DataInterface;

class SearchResults extends \Magento\Framework\Api\SearchResults implements SearchResultInterface
{
    /**
     * @var ModelFactory
     */
    protected $modelFactory;

    /**
     * @param ModelFactory $modelFactory
     * @param array $data
     */
    public function __construct(
        ModelFactory $modelFactory,
        array        $data = []
    ) {
        $this->modelFactory = $modelFactory;
        parent::__construct($data);
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
     * @return DataInterface[]
     */
    public function getItems()
    {
        return $this->_get(self::KEY_ITEMS) === null ? [] : $this->_get(self::KEY_ITEMS);
    }

    /**
     * @param DataInterface[] $items
     * @return $this
     */
    public function setItems(array $items)
    {
        $_items = [];
        foreach ($items as $item) {
            $_items[] = $this->modelFactory->create()->setData($item->getData());
        }
        return $this->setData(self::KEY_ITEMS, $_items);
    }
}
