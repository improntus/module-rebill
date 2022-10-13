<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2022 Improntus (http://www.improntus.com/)
 * @package Improntus_Rebill
 */

namespace Improntus\Rebill\Api\Payment;

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
    public function getStatus(): ?string;

    /**
     * @param string $status
     * @return DataInterface
     */
    public function setStatus(string $status): DataInterface;

    /**
     * @return array|null
     */
    public function getDetails(): ?array;

    /**
     * @param array $details
     * @return DataInterface
     */
    public function setDetails(array $details): DataInterface;
}
