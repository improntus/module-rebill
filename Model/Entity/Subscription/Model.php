<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2022 Improntus (http://www.improntus.com/)
 * @package Improntus_Rebill
 */

namespace Improntus\Rebill\Model\Entity\Subscription;

use Improntus\Rebill\Api\Subscription\DataInterface;
use Improntus\Rebill\Model\ResourceModel\Subscription;
use Magento\Framework\Model\AbstractModel;

class Model extends AbstractModel implements DataInterface
{
    /**
     * @var string
     */
    protected $_resourceName = Subscription::class;

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
}
