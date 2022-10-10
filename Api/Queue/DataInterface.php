<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2022 Improntus (http://www.improntus.com/)
 * @package Improntus_Rebill
 */

namespace Improntus\Rebill\Api\Queue;

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
     * @return string
     */
    public function getType(): string;

    /**
     * @param string $type
     * @return DataInterface
     */
    public function setType(string $type): DataInterface;

    /**
     * @return string
     */
    public function getStatus(): string;

    /**
     * @param string $status
     * @return DataInterface
     */
    public function setStatus(string $status): DataInterface;

    /**
     * @return array
     */
    public function getParameters(): array;

    /**
     * @param array $parameters
     * @return DataInterface
     */
    public function setParameters(array $parameters): DataInterface;
}
