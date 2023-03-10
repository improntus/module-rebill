<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2022 Improntus (http://www.improntus.com/)
 * @package Improntus_Rebill
 */

namespace Improntus\Rebill\Ui\Component\Listing\Column;

use Magento\Backend\Model\Url;
use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\View\Element\UiComponent\ContextInterface;

class QueueData extends Column
{
    /**
     * @var Url
     */
    protected $urlBuilder;

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param Url $urlBuilder
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface   $context,
        UiComponentFactory $uiComponentFactory,
        Url                $urlBuilder,
        array              $components = [],
        array              $data = []
    ) {
        $this->urlBuilder = $urlBuilder;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                $name = $this->getData('name');
                $parameters = json_decode($item['parameters'], true);
                switch ($item['type']) {
                    case 'payment_change_status':
                        $item[$name] = __("Rebill Payment: %1",$parameters['payment']['id']) . "<br>";
                        $item[$name] .= __("New Status: %1",$parameters['payment']['newStatus']) . "<br>";
                        $item[$name] .= __("Previous Status: %1",__($parameters['payment']['previousStatus']));
                        break;
                    case 'subscription_change_status':
                        $item[$name] = __("Rebill Subscription: %1",$parameters['billingScheduleId']) ."<br>";
                        $item[$name] .= __("New Status: %1",$parameters['newStatus']) . "<br>";
                        $item[$name] .= __("Previous Status: %1",$parameters['oldStatus']);
                        break;
                    case 'heads_up':
                        $item[$name] = __("Rebill Subscription: %1",$parameters['id']) . "<br>";
                        $_nextChargeDate = $parameters['_nextChargeDate'] ?? "";
                        $item[$name] .= __("Date: %1",$_nextChargeDate);
                        break;
                    case 'confirmation':
                        $item[$name] = "Rebill Invoice: {$parameters['invoice_id']}<br>";
                        $item[$name] .= __("Magento Order ID: %1", $parameters['order_id']);
                        break;
                    case 'new_payment':
                    default:
                        $item[$name] = "Rebill Payment: {$parameters['payment']['id']}<br>";
                        $item[$name] .= __("Status: %1", __($parameters['payment']['status']));
                        break;
                }
            }
        }
        return $dataSource;
    }
}
