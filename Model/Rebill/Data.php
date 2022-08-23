<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2022 Improntus (http://www.improntus.com/)
 * @package Improntus_Rebill
 */

namespace Improntus\Rebill\Model\Rebill;

use Exception;
use Improntus\Rebill\Helper\Config;
use Improntus\Rebill\HTTP\Client\Curl;
use Improntus\Rebill\Model\Rebill;

class Data extends Rebill
{
    /**
     * @var Gateway
     */
    protected $gateway;

    /**
     * @param Curl $curl
     * @param Config $configHelper
     * @param Gateway $gateway
     */
    public function __construct(
        Curl    $curl,
        Config  $configHelper,
        Gateway $gateway
    ) {
        $this->gateway = $gateway;
        parent::__construct($curl, $configHelper);
    }

    /**
     * @return mixed|null
     */
    public function getIdentificationsByGateway()
    {
        try {
            $gatewayId = $this->configHelper->getGatewayId();
            $gateways = $this->gateway->getGateways();
            $gateway = 'mercadopago';
            foreach ($gateways as $_gateway) {
                if ($_gateway['id'] == $gatewayId) {
                    $gateway = $_gateway['type'];
                }
            }
            $country = $this->configHelper->getCountry();
            return $this->request('identification', 'GET', [$gateway, $country], [], [], false);
        } catch (Exception $exception) {
            $this->configHelper->logError($exception->getMessage());
        }
        return null;
    }
}
