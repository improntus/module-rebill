<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2022 Improntus (http://www.improntus.com/)
 * @package Improntus_Rebill
 */

namespace Improntus\Rebill\Ui\Component\Listing\Column;

use Improntus\Rebill\Helper\Subscription;
use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\View\Element\UiComponent\ContextInterface;

class Frequency extends Column
{
    /**
     * @var Subscription
     */
    protected $subscription;

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param Subscription $subscription
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface   $context,
        UiComponentFactory $uiComponentFactory,
        Subscription       $subscription,
        array              $components = [],
        array              $data = []
    ) {
        $this->subscription = $subscription;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * Prepare Data Source
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (!isset($dataSource['data']['items'])) {
            return $dataSource;
        }

        foreach ($dataSource['data']['items'] as &$item) {
            $name = $this->getData('name');
            if (!isset($item['frequency'])) {
                continue;
            }

            if ($item['frequency'] == 1) {
                $item[$name] = $item['frequency_type'] == 'months' ? __('monthly') : __('yearly');
            } else {
                $item[$name] = __("%1 %2", $item['frequency'], $item['frequency_type']);
            }

        }

        return $dataSource;
    }
}
