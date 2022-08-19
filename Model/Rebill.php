<?php

namespace Improntus\Rebill\Model;

use Exception;
use Improntus\Rebill\Helper\Config;
use Improntus\Rebill\HTTP\Client\Curl;
use Magento\Framework\Session\SessionManagerInterface;

class Rebill
{
    /**
     * @var Curl
     */
    protected $curl;

    /**
     * @var Config
     */
    protected $configHelper;

    /**
     * @var SessionManagerInterface
     */
    protected $session;

    /**
     * @var string
     */
    protected $baseUrl = '';

    /**
     * @var string[]
     */
    protected $endpoints = [
        'auth'                      => '/v2/auth/login/%s',
        'create_item'               => '/v2/item',
        'update_item'               => '/v2/item/%s',
        'create_price'              => '/v2/item/%s/price',
        'update_price'              => '/v2/item/price/%s',
        'checkout'                  => '/v2/checkout',
        'customer_subscriptions'    => '/v2/subscriptions/customer/%s',
        'identification'            => '/v2/data/identification/%s/%s',
        'organization_info'         => '/v2/organization',
        'payment'                   => '/v2/payments/%s',
        'payment_list'              => '/v2/payments',
        'card'                      => '/v2/clients/cards/%s',
        'subscription'              => '/v2/subscriptions/customer/%s',
        'cancel_subscription'       => '/v2/clients/subscriptions/%s',
        'subscription_list'         => '/v2/subscriptions/%s/all',
        'subscription_change_price' => '/v2/subscriptions/%s/change-plan',
        'invoice'                   => '/v2/receipts/%s',
    ];

    /**
     * @param Curl $curl
     * @param Config $configHelper
     */
    public function __construct(
        Curl   $curl,
        Config $configHelper
    ) {
        $this->curl = $curl;
        $this->configHelper = $configHelper;

        if ($configHelper->getIntegrationMode() == 'sandbox') {
            $this->baseUrl = 'https://sandbox.rebill.to';
        } else {
            $this->baseUrl = 'https://api.rebill.to';
        }
    }

    /**
     * @param SessionManagerInterface $session
     * @return $this
     */
    public function setSession(SessionManagerInterface $session)
    {
        $this->session = $session;
        return $this;
    }

    /**
     * @return string|null
     */
    protected function getToken()
    {
        if (!$this->session) {
            return null;
        }
        return $this->session->getData('rebill_token') ?? null;
    }

    /**
     * @param $token
     * @return $this|null
     */
    protected function setToken($token)
    {
        if (!$this->session) {
            return null;
        }
        $this->session->setData('rebill_token', $token);
        return $this;
    }

    /**
     * @return string|null
     */
    protected function auth()
    {
        $authParams = [
            'email'    => $this->configHelper->getApiUser(),
            'password' => $this->configHelper->getApiPassword(),
        ];
        $result = $this->request('auth', 'POST', [$this->configHelper->getApiAlias()], $authParams);
        $this->setToken($result['authToken']);
        return $result['authToken'] ?? null;
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
     * @param $endpoint
     * @param string $method
     * @param array $urlData
     * @param array $data
     * @param array $options
     * @param bool $needToken
     * @return mixed|null
     */
    protected function request(
        $endpoint,
        string $method = 'GET',
        array $urlData = [],
        array $data = [],
        array $options = [],
        bool $needToken = true
    ) {
        $token = '';
        if ($endpoint != 'auth' && $needToken) {
            $token = $this->getToken() ?: $this->auth();
        }
        if ($endpoint != 'auth' && !$token && $needToken) {
            return null;
        }
        $method = $method ?? "GET";
        $curl = $this->curl;
        try {
            $headers = ["Content-Type" => "application/json", 'Accept' => 'application/json'];
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
            return json_decode($result, true);
        } catch (Exception $e) {
            $this->configHelper->logError($e->getMessage());
        }
        return null;
    }
}
