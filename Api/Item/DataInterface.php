<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2022 Improntus (http://www.improntus.com/)
 * @package Improntus_Rebill
 */

namespace Improntus\Rebill\Api\Item;

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
    public function getSku(): ?string;

    /**
     * @param string $sku
     * @return DataInterface
     */
    public function setSku(string $sku): DataInterface;

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
    public function getDescription(): ?string;

    /**
     * @param string $description
     * @return DataInterface
     */
    public function setDescription(string $description): DataInterface;

    /**
     * @return string|null
     */
    public function getHash(): ?string;

    /**
     * @param string $hash
     * @return DataInterface
     */
    public function setHash(string $hash): DataInterface;
}
