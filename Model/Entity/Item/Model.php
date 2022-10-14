<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2022 Improntus (http://www.improntus.com/)
 * @package Improntus_Rebill
 */

namespace Improntus\Rebill\Model\Entity\Item;

use Improntus\Rebill\Api\Item\DataInterface;
use Improntus\Rebill\Model\ResourceModel\Item;
use Improntus\Rebill\Model\ResourceModel\Item\Collection;
use Magento\Framework\Model\AbstractModel;

class Model extends AbstractModel implements DataInterface
{
    /**
     * @var string
     */
    protected $_resourceName = Item::class;

    /**
     * @var string
     */
    protected $_collectionName = Collection::class;

    /**
     * @var string
     */
    protected $_idFieldName = 'entity_id';

    /**
     * @return string|null
     */
    public function getSku(): ?string
    {
        return $this->getData('product_sku');
    }

    /**
     * @param string $sku
     * @return DataInterface
     */
    public function setSku(string $sku): DataInterface
    {
        $this->setData('product_sku', $sku);
        return $this;
    }

    /**
     * @return string|null
     */
    public function getRebillId(): ?string
    {
        return $this->getData('rebill_item_id');
    }

    /**
     * @param string $rebillId
     * @return DataInterface
     */
    public function setRebillId(string $rebillId): DataInterface
    {
        $this->setData('rebill_item_id', $rebillId);
        return $this;
    }

    /**
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->getData('product_description');
    }

    /**
     * @param string $description
     * @return DataInterface
     */
    public function setDescription(string $description): DataInterface
    {
        $this->setData('product_description', $description);
        return $this;
    }

    /**
     * @return string|null
     */
    public function getHash(): ?string
    {
        return $this->getData('hash');
    }

    /**
     * @param string $hash
     * @return DataInterface
     */
    public function setHash(string $hash): DataInterface
    {
        $this->setData('hash', $hash);
        return $this;
    }
}
