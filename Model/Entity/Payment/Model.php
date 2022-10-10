<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2022 Improntus (http://www.improntus.com/)
 * @package Improntus_Rebill
 */

namespace Improntus\Rebill\Model\Entity\Payment;

use Improntus\Rebill\Api\Payment\DataInterface;
use Improntus\Rebill\Model\ResourceModel\Payment;
use Magento\Framework\Model\AbstractModel;

class Model extends AbstractModel implements DataInterface
{
    /**
     * @var string
     */
    protected $_resourceName = Payment::class;

    /**
     * @return int
     */
    public function getSubscriptionId(): int
    {
        return $this->getData('subscription_id');
    }

    /**
     * @param int $subscriptionId
     * @return DataInterface
     */
    public function setSubscriptionId(int $subscriptionId): DataInterface
    {
        $this->setData('subscription_id', $subscriptionId);
        return $this;
    }

    /**
     * @return string
     */
    public function getRebillId(): string
    {
        return $this->getData('rebill_id');
    }

    /**
     * @param string $rebillId
     * @return DataInterface
     */
    public function setRebillId(string $rebillId): DataInterface
    {
        $this->setData('rebill_id', $rebillId);
        return $this;
    }

    /**
     * @return string
     */
    public function getStatus(): string
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
     * @return array
     */
    public function getDetails(): array
    {
        return json_decode($this->getData('details'), true);
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
}
