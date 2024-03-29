<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2022 Improntus (http://www.improntus.com/)
 * @package Improntus_Rebill
 */

namespace Improntus\Rebill\Model\Entity\Subscription;

use Improntus\Rebill\Api\Subscription\DataInterface;
use Improntus\Rebill\Model\ResourceModel\Subscription;
use Improntus\Rebill\Model\ResourceModel\Subscription\Collection;
use Magento\Framework\Model\AbstractModel;

class Model extends AbstractModel implements DataInterface
{
    public const STATUS_ACTIVE = 'ACTIVE';
    public const STATUS_PAUSED = 'PAUSED';
    public const STATUS_DEFAULT = 'DEFAULT';
    public const STATUS_RETRY = 'RETRYING';
    public const STATUS_CANCELLED = 'CANCELLED';
    public const STATUS_FINISHED = 'FINISHED';

    /**
     * @var string
     */
    protected $_resourceName = Subscription::class;

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
    public function getShipmentId(): ?int
    {
        return $this->getData('shipment_id');
    }

    /**
     * @param int $shipmentId
     * @return DataInterface
     */
    public function setShipmentId(int $shipmentId): DataInterface
    {
        $this->setData('shipment_id', $shipmentId);
        return $this;
    }

    /**
     * @return string|null
     */
    public function getRebillId(): ?string
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
     * @param string $status
     * @return DataInterface
     */
    public function setStatus(string $status): DataInterface
    {
        $this->setData('status', $status);
        return $this;
    }

    /**
     * @return int|null
     */
    public function getQuantity(): ?int
    {
        return $this->getData('quantity');
    }

    /**
     * @param int $quantity
     * @return DataInterface
     */
    public function setQuantity(int $quantity): DataInterface
    {
        $this->setData('quantity', $quantity);
        return $this;
    }

    /**
     * @return int|null
     */
    public function getOrderId(): ?int
    {
        return $this->getData('order_id');
    }

    /**
     * @param int $orderId
     * @return DataInterface
     */
    public function setOrderId(int $orderId): DataInterface
    {
        $this->setData('order_id', $orderId);
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
     * @return string|null
     */
    public function getPackageHash(): ?string
    {
        return $this->getData('package_hash');
    }

    /**
     * @param string $packageHash
     * @return DataInterface
     */
    public function setPackageHash(string $packageHash): DataInterface
    {
        $this->setData('package_hash', $packageHash);
        return $this;
    }

    /**
     * @return string|null
     */
    public function getNextSchedule(): ?string
    {
        return $this->getData('next_schedule');
    }

    /**
     * @param string $nextSchedule
     * @return DataInterface
     */
    public function setNextSchedule(string $nextSchedule): DataInterface
    {
        $this->setData('next_schedule', $nextSchedule);
        return $this;
    }

    /**
     * @return int|null
     */
    public function getPayed(): ?int
    {
        return $this->getData('payed');
    }

    /**
     * @param int $payed
     * @return DataInterface
     */
    public function setPayed(int $payed): DataInterface
    {
        $this->setData('payed', $payed);
        return $this;
    }

    /**
     * @return bool
     */
    public function canUpdateIt(): bool
    {
        return in_array($this->getStatus(), [
            static::STATUS_ACTIVE,
            static::STATUS_DEFAULT,
            static::STATUS_RETRY,
        ]);
    }

    /**
     * @return string|null
     */
    public function getStatus(): ?string
    {
        return $this->getData('status');
    }

    /**
     * @return bool
     */
    public function canReactivateIt(): bool
    {
        return $this->getStatus() == static::STATUS_PAUSED;
    }

    /**
     * @return bool
     */
    public function canPauseIt(): bool
    {
        return $this->getStatus() == static::STATUS_ACTIVE;
    }

    /**
     * @return bool
     */
    public function hasNextScheduledPayment(): bool
    {
        return !in_array($this->getStatus(), [
            static::STATUS_CANCELLED,
            static::STATUS_FINISHED,
        ]);
    }

    /**
     * @return bool
     */
    public function canCancelIt(): bool
    {
        return static::canCancelSubscription($this->getStatus());
    }

    /**
     * @param string $currentStatus
     * @return bool
     * @phpcs:disable
     */
    public static function canCancelSubscription(string $currentStatus): bool
    {
        return in_array($currentStatus, [
            static::STATUS_ACTIVE,
            static::STATUS_PAUSED,
        ]);
    }
}
