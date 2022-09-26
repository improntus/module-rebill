<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2022 Improntus (http://www.improntus.com/)
 * @package Improntus_Rebill
 */

namespace Improntus\Rebill\Model\Rebill;

use Exception;
use Improntus\Rebill\Model\Rebill;

class Subscription extends Rebill
{
    /**
     * @param $email
     * @return mixed|null
     */
    public function getSubscriptionFromClientEmail($email)
    {
        try {
            return $this->request('subscription', 'GET', [$email]);
        } catch (Exception $exception) {
            $this->configHelper->logError($exception->getMessage());
        }
        return [];
    }

    /**
     * @return array|mixed|null
     */
    public function getSubscriptionFromClient($customerEmail)
    {
        try {
            return $this->request('client_subscription_list', 'GET', [], ['customerEmail' => $customerEmail]);
        } catch (Exception $exception) {
            $this->configHelper->logError($exception->getMessage());
        }
        return [];
    }

    /**
     * @param $id
     * @param $customerEmail
     * @return mixed|null
     * @throws Exception
     */
    public function cancelSubscription($id, $customerEmail)
    {
        try {
            return $this->request('client_subscription', 'DELETE', [$id], ['customerEmail' => $customerEmail]);
        } catch (Exception $exception) {
            $this->configHelper->logError($exception->getMessage());
            throw $exception;
        }
    }

    /**
     * @param $id
     * @param $customerEmail
     * @return mixed|null
     * @throws Exception
     */
    public function getSubscription($id, $customerEmail)
    {
        try {
            return $this->request('client_subscription', 'GET', [$id], ['customerEmail' => $customerEmail]);
        } catch (Exception $exception) {
            $this->configHelper->logError($exception->getMessage());
            throw $exception;
        }
    }

    /**
     * @param $customerEmail
     * @return mixed|null
     * @throws Exception
     */
    public function getCustomerToken($customerEmail)
    {
        try {
            return $this->request('customer_auth', 'POST', [], ['customerEmail' => $customerEmail]);
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

    /**
     * @param $id
     * @return mixed|null
     * @throws Exception
     */
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
