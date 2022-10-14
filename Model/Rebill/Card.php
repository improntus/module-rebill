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
     * @param string $cardId
     * @param string $customerEmail
     * @return mixed|null
     */
    public function getCard(string $cardId, string $customerEmail)
    {
        try {
            return $this->request('card', 'GET', [$cardId], ['customerEmail' => $customerEmail]);
        } catch (Exception $exception) {
            $this->configHelper->logError($exception->getMessage());
        }
        return null;
    }

    /**
     * @param string $id
     * @return mixed|null
     */
    public function getCards(string $id)
    {
        try {
            return $this->request('subscription_cards', 'GET', [$id]);
        } catch (Exception $exception) {
            $this->configHelper->logError($exception->getMessage());
        }
        return null;
    }
}
