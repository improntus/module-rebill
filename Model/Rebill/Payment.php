<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2022 Improntus (http://www.improntus.com/)
 * @package Improntus_Rebill
 */

namespace Improntus\Rebill\Model\Rebill;

use Exception;
use Improntus\Rebill\Model\Rebill;

class Payment extends Rebill
{
    /**
     * @return mixed|null
     * @throws Exception
     */
    public function getList()
    {
        try {
            return $this->request('payment_list');
        } catch (Exception $exception) {
            $this->configHelper->logError($exception->getMessage());
            throw $exception;
        }
    }

    /**
     * @param string $id
     * @return mixed|null
     * @throws Exception
     */
    public function getPaymentById(string $id)
    {
        try {
            return $this->request('payment', 'GET', [$id]);
        } catch (Exception $exception) {
            $this->configHelper->logError($exception->getMessage());
            throw $exception;
        }
    }
}
