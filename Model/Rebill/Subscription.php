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
     * @param string $email
     * @return array|mixed|null
     */
    public function getSubscriptionFromClientEmail(string $email)
    {
        try {
            return $this->request('subscription', 'GET', [$email]);
        } catch (Exception $exception) {
            $this->configHelper->logError($exception->getMessage());
        }
        return [];
    }

    /**
     * @param string $customerEmail
     * @return array|mixed|null
     */
    public function getSubscriptionFromClient(string $customerEmail)
    {
        try {
            return $this->request('client_subscription_list', 'GET', [], ['customerEmail' => $customerEmail]);
        } catch (Exception $exception) {
            $this->configHelper->logError($exception->getMessage());
        }
        return [];
    }

    /**
     * @param string $id
     * @param string $customerEmail
     * @return mixed|null
     * @throws Exception
     */
    public function cancelSubscription(string $id, string $customerEmail)
    {
        try {
            return $this->request('client_subscription', 'DELETE', [$id], ['customerEmail' => $customerEmail]);
        } catch (Exception $exception) {
            $this->configHelper->logError($exception->getMessage());
            throw $exception;
        }
    }

    /**
     * @param string $id
     * @param string $customerEmail
     * @return mixed|null
     * @throws Exception
     */
    public function getSubscription(string $id, string $customerEmail)
    {
        try {
            return $this->request('client_subscription', 'GET', [$id], ['customerEmail' => $customerEmail]);
        } catch (Exception $exception) {
            $this->configHelper->logError($exception->getMessage());
            throw $exception;
        }
    }

    /**
     * @param string $customerEmail
     * @return mixed|null
     * @throws Exception
     */
    public function getCustomerToken(string $customerEmail)
    {
        try {
            return $this->request('customer_auth', 'POST', [], ['customerEmail' => $customerEmail]);
        } catch (Exception $exception) {
            $this->configHelper->logError($exception->getMessage());
            throw $exception;
        }
    }

    /**
     * @param string $type
     * @return mixed|null
     * @throws Exception
     */
    public function getList(string $type)
    {
        try {
            return $this->request('subscription_list', 'GET', [$type]);
        } catch (Exception $exception) {
            $this->configHelper->logError($exception->getMessage());
            throw $exception;
        }
    }

    /**
     * @param string $subscriptionId
     * @param string $priceId
     * @return mixed|null
     * @throws Exception
     */
    public function changePrice(string $subscriptionId, string $priceId)
    {
        try {
            return $this->request('subscription_change_price', 'POST', [$subscriptionId], ['price_id' => $priceId]);
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
    public function getInvoice(string $id)
    {
        try {
            return $this->request('invoice', 'GET', [$id]);
        } catch (Exception $exception) {
            $this->configHelper->logError($exception->getMessage());
            throw $exception;
        }
    }
}
