<?php
/*
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2022 Improntus (http://www.improntus.com/)
 * @package Improntus_Rebill
 */

namespace Improntus\Rebill\Api;

interface WebhookInterface
{
    /**
     * @param string $type
     * @return mixed
     */
    public function execute(string $type);
}
