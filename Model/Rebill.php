<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2022 Improntus (http://www.improntus.com/)
 * @package Improntus_Rebill
 */

namespace Improntus\Rebill\Model;

use Exception;
use Improntus\Rebill\Helper\Config;
use Improntus\Rebill\HTTP\Client\Curl;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\Session\SessionManagerInterface;

class Rebill
{
    /**
     * @var Config
     */
    protected $configHelper;

    /**
     * @var string|null
     */
    protected $token;

    /**
     * @var Curl
     */
    private $curl;

    /**
     * @var string
     */
    private $baseUrl = '';

    /**
     * @var string[]
     */
    private $endpoints = [
        'auth' => '/v2/auth/login/%s',
        'customer_auth' => '/v2/organization/customer-token',
        'create_item' => '/v2/item',
        'update_item' => '/v2/item/%s',
        'create_price' => '/v2/item/%s/price',
        'update_price' => '/v2/item/price/%s',
        'create_price_settings' => '/v2/item/price/%s/settings',
        'checkout' => '/v2/checkout',
        'customer_subscriptions' => '/v2/subscriptions/customer/%s',
        'identification' => '/v2/data/identification/%s/%s',
        'organization_info' => '/v2/organization',
        'getcurrencies' => '/v2/data/currencies/%s/%s',
        'payment' => '/v2/payments/%s',
        'payment_list' => '/v2/payments',
        'payment_subscriptions' => '/v2/payments/%s/billingSchedules',
        'card' => '/v2/clients/cards/%s',
        'cards' => '/v2/clients/cards',
        'subscription' => '/v2/subscriptions/customer/%s',
        'client_subscription_list' => '/v2/clients/subscriptions',
        'client_subscription' => '/v2/clients/subscriptions/%s',
        'update_subscription' => '/v2/subscriptions/%s',
        'subscription_cards' => '/v2/subscriptions/%s/customer_cards',
        'subscription_list' => '/v2/subscriptions/%s/all',
        'subscription_change_price' => '/v2/subscriptions/%s/change-plan',
        'invoice' => '/v2/receipts/%s',
        'refund' => '/v2/refund',
    ];
    /**
     * @var string[]
     */
    private $customerEndpoints = [
        'client_subscription_list',
        'client_subscription',
        'card',
        'cards',
    ];

    /**
     * @var CacheInterface
     */
    private $cacheManager;

    /**
     * @param Curl $curl
     * @param Config $configHelper
     * @param CacheInterface $cacheManager
     */
    public function __construct(
        Curl           $curl,
        Config         $configHelper,
        CacheInterface $cacheManager
    ) {
        $this->curl = $curl;
        $this->configHelper = $configHelper;
        $this->cacheManager = $cacheManager;
        $this->baseUrl = 'https://api.rebill.to';
    }

    /**
     * @return string|null
     */
    protected function auth()
    {
        if ($this->configHelper->getUseApiKey()) {
            $token = $this->configHelper->getApiKey();
            $this->setToken($token);
        } else {
            $token = $this->cacheManager->load('rebill_token');
            if (!$token) {
                $authParams = [
                    'email' => $this->configHelper->getApiUser(),
                    'password' => $this->configHelper->getApiPassword(),
                ];
                $result = $this->request('auth', 'POST', [$this->configHelper->getApiAlias()], $authParams);
                $this->setToken($result['authToken'] ?? null);
                $this->cacheManager->save(
                    $result['authToken'] ?? null,
                    'rebill_token',
                    [],
                    72000 //20 hours in seconds
                );
            } else {
                $this->setToken($token);
            }
        }
        return $this->getToken();
    }

    /**
     * @param string $endpoint
     * @param string $method
     * @param array $urlData
     * @param array $data
     * @param array $options
     * @param bool $needToken
     * @param bool $repeat
     * @return mixed|null
     */
    protected function request(
        string $endpoint,
        string $method = 'GET',
        array  $urlData = [],
        array  $data = [],
        array  $options = [],
        bool   $needToken = true,
        bool   $repeat = true
    ) {
        $origParams = [
            'endpoint' => $endpoint,
            'method' => $method,
            'urlData' => $urlData,
            'data' => $data,
            'options' => $options,
            'needToken' => $needToken,
        ];
        $token = '';
        if ($endpoint != 'auth' && $needToken) {
            $token = $this->getToken() ?: $this->auth();
        }
        if ($endpoint != 'auth' && !$token && $needToken) {
            return null;
        }
        if (in_array($endpoint, $this->customerEndpoints)) {
            $token = $this->customerAuth($data['customerEmail']);
            unset($data['customerEmail']);
        }
        $method = $method ?? "GET";
        $curl = $this->curl;
        try {
            $headers = ["Content-Type" => "application/json", 'Accept' => 'application/json'];
            $headers['Cache-Control'] = 'no-cache';
            if ($token && $needToken) {
                $headers['Authorization'] = "Bearer $token";
            }
            if (isset($options['headers'])) {
                $headers = array_merge($headers, $options['headers']);
                unset($options['headers']);
            }
            if ($options) {
                $curl->setOptions($options);
            }
            $curl->setHeaders($headers);
            $url = $this->getEndpointUrl($endpoint, $urlData ?? []);
            if (count($data) && $method == 'GET') {
                $url .= '?' . http_build_query($data);
            }
            $this->configHelper->logInfo($url);
            switch ($method) {
                case "POST":
                    $curl->post($url, json_encode($data));
                    break;
                case "GET":
                    $curl->get($url);
                    break;
                case "PUT":
                    $curl->put($url, json_encode($data));
                    break;
                case "DELETE":
                    $curl->delete($url);
                    break;
            }
            $result = $curl->getBody();
            $this->configHelper->logInfo(json_encode($data));
            $this->configHelper->logInfo($result);
            if (stristr($result, 'Invalid or expired') !== false
                || stristr($result, 'Unauthorized') !== false && $repeat) {
                $this->cacheManager->remove('rebill_token');
                return $this->request(
                    $origParams['endpoint'],
                    $origParams['method'],
                    $origParams['urlData'],
                    $origParams['data'],
                    $origParams['options'],
                    $origParams['needToken'],
                    false
                );
            }
            return json_decode($result, true);
        } catch (Exception $e) {
            $this->configHelper->logError($e->getMessage());
        }
        return null;
    }

    /**
     * @return string|null
     */
    protected function getToken()
    {
        return $this->token;
    }

    /**
     * @param string $customerEmail
     * @return mixed|null
     */
    public function customerAuth(string $customerEmail)
    {
        $authParams = [
            'customerEmail' => $customerEmail,
        ];
        $result = $this->request('customer_auth', 'POST', [], $authParams);
        return $result['token'] ?? null;
    }

    /**
     * @param string $endpoint
     * @param array $ids
     * @return string
     */
    protected function getEndpointUrl(string $endpoint, array $ids = []): string
    {
        if (!isset($this->endpoints[$endpoint])) {
            return false;
        }
        if ($ids) {
            return $this->baseUrl . vsprintf($this->endpoints[$endpoint], $ids);
        }
        return $this->baseUrl . $this->endpoints[$endpoint];
    }

    /**
     * @param string|null $token
     * @return $this|null
     */
    protected function setToken(?string $token)
    {
        $this->token = $token;
        return $this;
    }
}
