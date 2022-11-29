<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2022 Improntus (http://www.improntus.com/)
 * @package Improntus_Rebill
 */

namespace Improntus\Rebill\Model\Rebill;

use Exception;
use Improntus\Rebill\Model\Rebill;

class Item extends Rebill
{
    /**
     * @param array $data
     * @return mixed|null
     */
    public function createItem(array $data = [])
    {
        try {
            $result = $this->request('create_item', 'POST', [], $data);
            return $result['item']['id'];
        } catch (Exception $exception) {
            $this->configHelper->logError($exception->getMessage());
        }
        return null;
    }

    /**
     * @param string $itemId
     * @param array $data
     * @return mixed|null
     */
    public function createPriceForItem(string $itemId, array $data = [])
    {
        try {
            $result = $this->request('create_price', 'POST', [$itemId], $data);
            return $result['id'];
        } catch (Exception $exception) {
            $this->configHelper->logError($exception->getMessage());
            $this->configHelper->logError('id:' . $itemId . ' amount:' . $data['amount']);
        }
        return null;
    }

    /**
     * @param string $id
     * @return mixed|null
     */
    public function getPriceFromId(string $id)
    {
        try {
            /**
             * @note the endpoint says "update_price" but is based on method used:
             * to get a price use GET method, tu update a price use PUT
             */
            return $this->request(
                'update_price',
                'GET',
                [$id],
                [],
                ['headers' => ['organization_id' => $this->configHelper->getApiUuid()]]
            );
        } catch (Exception $exception) {
            $this->configHelper->logError($exception->getMessage());
        }
        return null;
    }

    /**
     * @param string $priceId
     * @param array $data
     * @return mixed|null
     */
    public function updatePrice(string $priceId, array $data = [])
    {
        try {
            return $this->request('update_price', 'PUT', [$priceId], $data);
        } catch (Exception $exception) {
            $this->configHelper->logError($exception->getMessage());
        }
        return null;
    }

    public function createPriceSetting(string $priceId, array $data = [])
    {
        try {
            return $this->request('create_price_settings', 'POST', [$priceId], $data);
        } catch (Exception $exception) {
            $this->configHelper->logError($exception->getMessage());
        }
        return null;
    }
}
