<?php

namespace Improntus\Rebill\Model\Rebill;

use Exception;
use Improntus\Rebill\Model\Rebill;

class Subscription extends Rebill
{
    /**
     * @param $email
     * @return mixed|null
     */
    public function getSubscriptionFromClient($email)
    {
        try {
            return $this->request('subscription', 'GET', [$email]);
        } catch (Exception $exception) {
            $this->configHelper->logError($exception->getMessage());
        }
        return [];
    }

    /**
     * @param $id
     * @return mixed|null
     * @throws Exception
     */
    public function cancelSubscription($id)
    {
        try {
            return $this->request('cancel_subscription', 'DELETE', [$id]);
        } catch (Exception $exception) {
            $this->configHelper->logError($exception->getMessage());
            throw $exception;
        }
    }

    /**
     * @param $type
     * @return mixed|null
     * @throws Exception
     */
    public function getList($type)
    {
        try {
            return $this->request('subscription_list', 'GET', [$type]);
        } catch (Exception $exception) {
            $this->configHelper->logError($exception->getMessage());
            throw $exception;
        }
    }

    /**
     * @param $subscriptionId
     * @param $priceId
     * @return mixed|null
     * @throws Exception
     */
    public function changePrice($subscriptionId, $priceId)
    {
        try {
            return $this->request('subscription_change_price', 'POST', [$subscriptionId], ['price_id' => $priceId]);
        } catch (Exception $exception) {
            $this->configHelper->logError($exception->getMessage());
            throw $exception;
        }
    }

    public function getInvoice($id)
    {
        try {
            return $this->request('invoice', 'GET', [$id]);
        } catch (Exception $exception) {
            $this->configHelper->logError($exception->getMessage());
            throw $exception;
        }
    }
}
