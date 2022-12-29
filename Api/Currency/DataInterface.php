<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2022 Improntus (http://www.improntus.com/)
 * @package Improntus_Rebill
 */

namespace Improntus\Rebill\Api\Currency;

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
    public function getCurrencyId(): ?string;

    /**
     * @param int $itemId
     * @return DataInterface
     */
    public function setCurrencyId(string $itemId): DataInterface;

    /**
     * @return string|null
     */
    public function getSymbol(): ?string;

    /**
     * @param string $symbol
     * @return DataInterface
     */
    public function setSymbol(string $symbol): DataInterface;

    /**
     * @return string|null
     */
    public function getDescription(): ?string;

    /**
     * @param string $symbol
     * @return DataInterface
     */
    public function setDescription(string $description): DataInterface;
}
