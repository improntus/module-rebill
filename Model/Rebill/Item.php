<?php

namespace Improntus\Rebill\Model\Rebill;

use Exception;
use Improntus\Rebill\Model\Rebill;

class Item extends Rebill
{
    /**
     * @param $data
     * @return mixed|null
     */
    public function createItem($data = [])
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
     * @param $itemId
     * @param $data
     * @return mixed|null
     */
    public function createPriceForItem($itemId, $data = [])
    {
        try {
            $result = $this->request('create_price', 'POST', [$itemId], $data);
            return $result['id'];
        } catch (Exception $exception) {
            $this->configHelper->logError($exception->getMessage());
        }
        return null;
    }

    /**
     * @param $id
     * @return mixed|null
     */
    public function getPriceFromId($id)
    {
        try {
            /**
             * @note the endpoint says "update_price" but is based on method used: to get a price use GET method, tu update a price use PUT
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
     * @param $priceId
     * @param $data
     * @return mixed|null
     */
    public function updatePrice($priceId, $data = [])
    {
        try {
            return $this->request('update_price', 'PUT', [$priceId], $data);
        } catch (Exception $exception) {
            $this->configHelper->logError($exception->getMessage());
        }
        return null;
    }
}
