<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2022 Improntus (http://www.improntus.com/)
 * @package Improntus_Rebill
 */

namespace Improntus\Rebill\Observer;

use Improntus\Rebill\Helper\Config;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Improntus\Rebill\Model\Rebill;
use Improntus\Rebill\Model\Entity\Currency\Repository as CurrencyRepository;
use Improntus\Rebill\Model\Entity\Currency\Model as EntityCurrency;


class UpdateRebillCurrencies implements ObserverInterface
{
    /**
     * @var Config
     */
    protected $configHelper;
    /**
     * @var Rebill\Gateway
     */
    protected $gateway;
    /**
     * @var Rebill\Currency
     */
    protected $currency;
    /**
     * @var CurrencyRepository
     */
    protected $currencyRepository;


    /**
     * @param Config $configHelper
     * @param Rebill\Gateway $gateway
     * @param Rebill\Currency $currency
     */
    public function __construct(
        Config             $configHelper,
        Rebill\Gateway     $gateway,
        Rebill\Currency    $currency,
        CurrencyRepository $currencyRepository
    ) {
        $this->configHelper = $configHelper;
        $this->gateway = $gateway;
        $this->currency = $currency;
        $this->currencyRepository = $currencyRepository;
    }

    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $configData = $observer->getData('configData');
        if ($configData['section'] != 'payment') {
            return;
        }
        $gateways = $this->gateway->getGateways();
        $currencies = [];
        foreach ($gateways as $gateway) {
            $currenciesRebill = $this->currency->getCurrencies($gateway['type'], $gateway['country']);
            foreach ($currenciesRebill as $currency) {
                if (!in_array($currency, $currencies)) {
                    $currencies[] = $currency;
                }
            }
        }

        $lstCurrency = $this->currencyRepository->getCollection();

        foreach ($currencies as $currencyRebill) {

            /** @var EntityCurrency $currencyDB */
            $currencyDB = $lstCurrency->addFieldToSelect('*')
                ->addFieldToFilter('currency_id', ['eq' => $currencyRebill['id']])
                ->getFirstItem();


            if (!$currencyDB->getData()) {
                /** @var EntityCurrency $currency */
                $currency = $this->currencyRepository->create();
                $currency->setCurrencyId($currencyRebill['id']);
                $currency->setSymbol($currencyRebill['symbol']);
                $currency->setDescription($currencyRebill['description']);
                $this->currencyRepository->save($currency);
            }
        }

    }
}
