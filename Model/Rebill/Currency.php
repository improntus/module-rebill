<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2022 Improntus (http://www.improntus.com/)
 * @package Improntus_Rebill
 */

namespace Improntus\Rebill\Model\Rebill;

use Exception;
use Improntus\Rebill\Model\Rebill;

class Currency extends Rebill
{
    /**
     * @return array|mixed
     */
    public function getCurrencies($gateway, $isoCountry)
    {
        try {
            $result = $this->request('getcurrencies', 'GET', [$gateway, $isoCountry]);
            return $result;
        } catch (Exception $exception) {
            return [];
        }
    }
}
