<?php

namespace Improntus\Rebill\Model\Rebill;

use Exception;
use Improntus\Rebill\Model\Rebill;

class Gateway extends Rebill
{
    public function getGateways()
    {
        try {
            $result = $this->request('organization_info');
            return $result['gateways'];
        } catch (Exception $exception) {
            return [];
        }
    }
}
