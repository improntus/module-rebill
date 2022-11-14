<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2022 Improntus (http://www.improntus.com/)
 * @package Improntus_Rebill
 */

namespace Improntus\Rebill\Model\Entity\Queue;

use Improntus\Rebill\Api\Queue\DataInterface;
use Improntus\Rebill\Model\ResourceModel\Price;
use Improntus\Rebill\Model\ResourceModel\Queue\Collection;
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
    public function getStatus(): ?string
    {
        return $this->getData('status');
    }

    /**
     * @param string $status
     * @return DataInterface
     */
    public function setStatus(string $status): DataInterface
    {
        $this->setData('status', $status);
        return $this;
    }

    /**
     * @return array|null
     */
    public function getParameters(): ?array
    {
        return json_decode($this->getData('parameters'), true);
    }

    /**
     * @param array $parameters
     * @return DataInterface
     */
    public function setParameters(array $parameters): DataInterface
    {
        $this->setData('parameters', json_encode($parameters));
        return $this;
    }

    /**
     * @return string|null
     */
    public function getError(): ?string
    {
        return $this->getData('error');
    }

    /**
     * @param string $error
     * @return DataInterface
     */
    public function setError(string $error): DataInterface
    {
        $this->setData('error', $error);
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
