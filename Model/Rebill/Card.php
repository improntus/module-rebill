<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2022 Improntus (http://www.improntus.com/)
 * @package Improntus_Rebill
 */

namespace Improntus\Rebill\Model\Rebill;

use Exception;
use Improntus\Rebill\Model\Rebill;

class Card extends Rebill
{
    /**
     * @param $cardId
     * @return mixed|null
     */
    public function getCard($cardId, $customerEmail)
    {
        try {
            return $this->request('card', 'GET', [$cardId], ['customerEmail' => $customerEmail]);
        } catch (Exception $exception) {
            $this->configHelper->logError($exception->getMessage());
        }
        return null;
    }
}
