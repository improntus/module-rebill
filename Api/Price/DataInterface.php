<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2022 Improntus (http://www.improntus.com/)
 * @package Improntus_Rebill
 */

namespace Improntus\Rebill\Api\Price;

interface DataInterface
{
    /**
     * @return int|null
     */
    public function getId();

    /**
     * @param int $id
     * @return DataInterface
     */
    public function setId($id);

    /**
     * @return int|null
     */
    public function getItemId(): ?int;

    /**
     * @param int $itemId
     * @return DataInterface
     */
    public function setItemId(int $itemId): DataInterface;

    /**
     * @return string|null
     */
    public function getType(): ?string;

    /**
     * @param string $type
     * @return DataInterface
     */
    public function setType(string $type): DataInterface;

    /**
     * @return string|null
     */
    public function getRebillItemId(): ?string;

    /**
     * @param string $rebillItemId
     * @return DataInterface
     */
    public function setRebillItemId(string $rebillItemId): DataInterface;

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
    public function getDetailsHash(): ?string;

    /**
     * @param string $detailsHash
     * @return DataInterface
     */
    public function setDetailsHash(string $detailsHash): DataInterface;

    /**
     * @return string|null
     */
    public function getFrequencyHash(): ?string;

    /**
     * @param string $frequencyHash
     * @return DataInterface
     */
    public function setFrequencyHash(string $frequencyHash): DataInterface;

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
     * @return array|null
     */
    public function getRebillDetails(): ?array;

    /**
     * @param array $details
     * @return DataInterface
     */
    public function setRebillDetails(array $details): DataInterface;
}
