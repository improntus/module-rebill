<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2022 Improntus (http://www.improntus.com/)
 * @package Improntus_Rebill
 */

namespace Improntus\Rebill\Api\SubscriptionShipment;

interface DataInterface
{
    /**
     * @return int
     */
    public function getId();

    /**
     * @param int $id
     * @return DataInterface
     */
    public function setId($id);

    /**
     * @return string|null
     */
    public function getRebillId(): ?string;

    /**
     * @param string $rebillId
     * @return DataInterface
     */
    public function setRebillId(string $rebillId): DataInterface;

    /**
     * @return string|null
     */
    public function getRebillPriceId(): ?string;

    /**
     * @param string $rebillPriceId
     * @return DataInterface
     */
    public function setRebillPriceId(string $rebillPriceId): DataInterface;

    /**
     * @return string|null
     */
    public function getStatus(): ?string;

    /**
     * @param string $status
     * @return DataInterface
     */
    public function setStatus(string $status): DataInterface;

    /**
     * @return int|null
     */
    public function getQuantity(): ?int;

    /**
     * @param int $quantity
     * @return DataInterface
     */
    public function setQuantity(int $quantity): DataInterface;

    /**
     * @return int|null
     */
    public function getOrderId(): ?int;

    /**
     * @param int $orderId
     * @return DataInterface
     */
    public function setOrderId(int $orderId): DataInterface;

    /**
     * @return array|null
     */
    public function getDetails(): ?array;

    /**
     * @param array $details
     * @return DataInterface
     */
    public function setDetails(array $details): DataInterface;

    /**
     * @return int|null
     */
    public function getPayed(): ?int;

    /**
     * @param int $payed
     * @return DataInterface
     */
    public function setPayed(int $payed): DataInterface;

    /**
     * @return string|null
     */
    public function getNextSchedule(): ?string;

    /**
     * @param string $nextSchedule
     * @return DataInterface
     */
    public function setNextSchedule(string $nextSchedule): DataInterface;
}
