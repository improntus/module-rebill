<?php

namespace Improntus\Rebill\Model\Rebill;

use Exception;
use Improntus\Rebill\Model\Rebill;

class Card extends Rebill
{
    /**
     * @param $cardId
     * @return mixed|null
     */
    public function getCard($cardId)
    {
        try {
            return $this->request('card', 'GET', [$cardId]);
        } catch (Exception $exception) {
            $this->configHelper->logError($exception->getMessage());
        }
        return null;
    }
}
