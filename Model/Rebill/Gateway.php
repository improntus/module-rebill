<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2022 Improntus (http://www.improntus.com/)
 * @package Improntus_Rebill
 */

namespace Improntus\Rebill\Model\Rebill;

use Exception;
use Improntus\Rebill\Model\Rebill;

class Gateway extends Rebill
{
    /**
     * @return array|mixed
     */
    public function getGateways()
    {
        try {
            $result = $this->request('organization_info');
            return $result['gateways'];
        } catch (Exception $exception) {
            $this->configHelper->logError($exception->getMessage());
        }
    }
}
