<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2022 Improntus (http://www.improntus.com/)
 * @package Improntus_Rebill
 */

namespace Improntus\Rebill\Model\Entity\Currency;

use Improntus\Rebill\Api\Currency\DataInterface;
use Improntus\Rebill\Model\ResourceModel\Currency;
use Improntus\Rebill\Model\ResourceModel\Currency\Collection;
use Magento\Framework\Model\AbstractModel;

class Model extends AbstractModel implements DataInterface
{
    /**
     * @var string
     */
    protected $_resourceName = Currency::class;

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
    public function getCurrencyId(): ?string
    {
        return $this->getData('currency_id');
    }

    /**
     * @param string $currencyId
     * @return DataInterface
     */
    public function setCurrencyId(string $currencyId): DataInterface
    {
        $this->setData('currency_id', $currencyId);
        return $this;
    }

    /**
     * @return string|null
     */
    public function getSymbol(): ?string
    {
        return $this->getData('symbol');
    }

    /**
     * @param string $symbol
     * @return DataInterface
     */
    public function setSymbol(string $symbol): DataInterface
    {
        $this->setData('symbol', $symbol);
        return $this;
    }
    /**
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->getData('description');
    }

    /**
     * @param string $description
     * @return DataInterface
     */
    public function setDescription(string $description): DataInterface
    {
        $this->setData('description', $description);
        return $this;
    }

    /**
     * @return string|null
     */
    public function getUpdatedAt(): ?string
    {
        return $this->getData('updated_at');
    }
}
