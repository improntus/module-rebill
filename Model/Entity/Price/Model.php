<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2022 Improntus (http://www.improntus.com/)
 * @package Improntus_Rebill
 */

namespace Improntus\Rebill\Model\Entity\Price;

use Improntus\Rebill\Api\Price\DataInterface;
use Improntus\Rebill\Model\ResourceModel\Price;
use Improntus\Rebill\Model\ResourceModel\Price\Collection;
use Magento\Framework\Model\AbstractModel;

class Model extends AbstractModel implements DataInterface
{
    /**
     * @var string
     */
    protected $_resourceName = Price::class;

    /**
     * @var string
     */
    protected $_collectionName = Collection::class;

    /**
     * @var string
     */
    protected $_idFieldName = 'entity_id';

    /**
     * @return int|null
     */
    public function getItemId(): ?int
    {
        return $this->getData('item_id');
    }

    /**
     * @param int $itemId
     * @return DataInterface
     */
    public function setItemId(int $itemId): DataInterface
    {
        $this->setData('item_id', $itemId);
        return $this;
    }

    /**
     * @return string|null
     */
    public function getType(): ?string
    {
        return $this->getData('type');
    }

    /**
     * @param string $type
     * @return DataInterface
     */
    public function setType(string $type): DataInterface
    {
        $this->setData('type', $type);
        return $this;
    }

    /**
     * @return string|null
     */
    public function getRebillItemId(): ?string
    {
        return $this->getData('rebill_item_id');
    }

    /**
     * @param string $rebillItemId
     * @return DataInterface
     */
    public function setRebillItemId(string $rebillItemId): DataInterface
    {
        $this->setData('rebill_item_id', $rebillItemId);
        return $this;
    }

    /**
     * @return string|null
     */
    public function getRebillPriceId(): ?string
    {
        return $this->getData('rebill_price_id');
    }

    /**
     * @param string $rebillPriceId
     * @return DataInterface
     */
    public function setRebillPriceId(string $rebillPriceId): DataInterface
    {
        $this->setData('rebill_price_id', $rebillPriceId);
        return $this;
    }

    /**
     * @return string|null
     */
    public function getDetailsHash(): ?string
    {
        return $this->getData('details_hash');
    }

    /**
     * @param string $detailsHash
     * @return DataInterface
     */
    public function setDetailsHash(string $detailsHash): DataInterface
    {
        $this->setData('details_hash', $detailsHash);
        return $this;
    }

    /**
     * @return string|null
     */
    public function getFrequencyHash(): ?string
    {
        return $this->getData('frequency_hash');
    }

    /**
     * @param string $frequencyHash
     * @return DataInterface
     */
    public function setFrequencyHash(string $frequencyHash): DataInterface
    {
        $this->setData('frequency_hash', $frequencyHash);
        return $this;
    }

    /**
     * @return array|null
     */
    public function getDetails(): ?array
    {
        return json_decode($this->getData('details') ?? '', true);
    }

    /**
     * @param array $details
     * @return DataInterface
     */
    public function setDetails(array $details): DataInterface
    {
        $this->setData('details', json_encode($details));
        return $this;
    }

    /**
     * @return array|null
     */
    public function getRebillDetails(): ?array
    {
        return json_decode($this->getData('rebill_details'), true);
    }

    /**
     * @param array $details
     * @return DataInterface
     */
    public function setRebillDetails(array $details): DataInterface
    {
        $this->setData('rebill_details', json_encode($details));
        return $this;
    }
}
