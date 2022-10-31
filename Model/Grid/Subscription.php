<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2022 Improntus (http://www.improntus.com/)
 * @package Improntus_Rebill
 */

namespace Improntus\Rebill\Model\Grid;

use Improntus\Rebill\Model\ResourceModel\Subscription as SubscriptionModel;
use Magento\Framework\Api\ExtensionAttribute\JoinDataInterfaceFactory;
use Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Data\Collection\Db\FetchStrategyInterface as FetchStrategy;
use Magento\Framework\Data\Collection\EntityFactoryInterface as EntityFactory;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult;
use Psr\Log\LoggerInterface as Logger;
use Zend_Db_Expr;

class Subscription extends SearchResult
{
    /**
     * @var JoinDataInterfaceFactory
     */
    protected JoinDataInterfaceFactory $joinData;

    /**
     * @var JoinProcessorInterface
     */
    protected JoinProcessorInterface $joinProcessor;

    /**
     * @param EntityFactory $entityFactory
     * @param Logger $logger
     * @param FetchStrategy $fetchStrategy
     * @param EventManager $eventManager
     * @param JoinDataInterfaceFactory $joinData
     * @param JoinProcessorInterface $joinProcessor
     * @throws LocalizedException
     */
    public function __construct(
        EntityFactory            $entityFactory,
        Logger                   $logger,
        FetchStrategy            $fetchStrategy,
        EventManager             $eventManager,
        JoinDataInterfaceFactory $joinData,
        JoinProcessorInterface   $joinProcessor
    ) {
        $this->joinData = $joinData;
        $this->joinProcessor = $joinProcessor;
        $mainTable = 'rebill_subscription';
        $resourceModel = SubscriptionModel::class;
        parent::__construct($entityFactory, $logger, $fetchStrategy, $eventManager, $mainTable, $resourceModel);
    }

    /**
     * @return $this|Subscription|void
     * phpcs:disable
     */
    protected function _initSelect()
    {
        parent::_initSelect();

        $columns = [
            /**
             * Main Table Columns
             */
            "entity_id"           => "main_table.entity_id",
            "rebill_id"           => "main_table.rebill_id",
            "status"              => "main_table.status",
            "quantity"            => "main_table.quantity",
            "order_id"            => "main_table.order_id",
            "last_charge_date"    => "DATE_FORMAT(JSON_UNQUOTE(JSON_EXTRACT(main_table.details, '$.lastChargeDate')),'%Y-%m-%d')",
            "next_charge_date"    => "DATE_FORMAT(JSON_UNQUOTE(JSON_EXTRACT(main_table.details, '$.nextChargeDate')),'%Y-%m-%d')",
            "user_email"          => "JSON_UNQUOTE(JSON_EXTRACT(main_table.details, '$.userEmail'))",
            "title"               => "JSON_UNQUOTE(JSON_EXTRACT(main_table.details, '$.title'))",
            "amount"              => "IFNULL(JSON_UNQUOTE(JSON_EXTRACT(main_table.details, '$.price.amount')),'')",
            "frequency_type"      => "IFNULL(JSON_UNQUOTE(JSON_EXTRACT(main_table.details, '$.price.frequency.type')),'')",
            "frequency"           => "IFNULL(JSON_UNQUOTE(JSON_EXTRACT(main_table.details, '$.price.frequency.quantity')),'')",
            "repetitions"         => "IFNULL(JSON_UNQUOTE(JSON_EXTRACT(main_table.details, '$.price.repetitions')),'')",
            "gateway_type"        => "IFNULL(JSON_UNQUOTE(JSON_EXTRACT(main_table.details, '$.price.gateway.type')),'')",
            "gateway_description" => "IFNULL(JSON_UNQUOTE(JSON_EXTRACT(main_table.details, '$.price.gateway.description')),'')",
            /**
             * Order Columns
             */
            "increment_id"  => "o.increment_id",
        ];

        // o => Sales Order
        $this->getSelect()->joinLeft(['o' => 'sales_order'], 'main_table.order_id = o.entity_id', []);

        $this->getSelect()->reset('columns');
        foreach ($columns as $alias => $column) {
            $this->addFieldToSelect(new Zend_Db_Expr("{$column} AS {$alias}"));
        }
        return $this;
    }

    public function setOrder($field, $direction = self::SORT_ORDER_DESC)
    {
        $jsonFields = [
            "last_charge_date"    => "DATE_FORMAT(JSON_UNQUOTE(JSON_EXTRACT(main_table.details, '$.lastChargeDate')),'%Y-%m-%d')",
            "next_charge_date"    => "DATE_FORMAT(JSON_UNQUOTE(JSON_EXTRACT(main_table.details, '$.nextChargeDate')),'%Y-%m-%d')",
            "user_email"          => "JSON_UNQUOTE(JSON_EXTRACT(main_table.details, '$.userEmail'))",
            "title"               => "JSON_UNQUOTE(JSON_EXTRACT(main_table.details, '$.title'))",
            "amount"              => "IFNULL(JSON_UNQUOTE(JSON_EXTRACT(main_table.details, '$.price.amount')),'')",
            "frequency_type"      => "IFNULL(JSON_UNQUOTE(JSON_EXTRACT(main_table.details, '$.price.frequency.type')),'')",
            "frequency"           => "IFNULL(JSON_UNQUOTE(JSON_EXTRACT(main_table.details, '$.price.frequency.quantity')),'')",
            "repetitions"         => "IFNULL(JSON_UNQUOTE(JSON_EXTRACT(main_table.details, '$.price.repetitions')),'')",
            "gateway_type"        => "IFNULL(JSON_UNQUOTE(JSON_EXTRACT(main_table.details, '$.price.gateway.type')),'')",
            "gateway_description" => "IFNULL(JSON_UNQUOTE(JSON_EXTRACT(main_table.details, '$.price.gateway.description')),'')",
        ];
        if (in_array($field, array_keys($jsonFields))) {
            $this->getSelect()->order("{$jsonFields[$field]} $direction");
            return $this;
        }
        return parent::setOrder($field, $direction);
    }

    /**
     * @param $field
     * @param $condition
     * @return $this|Subscription
     */
    public function addFieldToFilter($field, $condition = null)
    {
        $aliasFilters = [
            'rebill_id' => 'main_table.rebill_id',
            'status'    => 'main_table.status',
            'quantity'  => 'main_table.quantity',
            'order_id'  => 'main_table.order_id',
        ];
        $customFilters = [
            'last_charge_date',
            'next_charge_date',
            'gateway_type',
            'gateway_description',
            'user_email',
            'title',
        ];
        if (in_array($field, $customFilters)) {
            switch ($field) {
                case 'next_charge_date':
                    $operator = isset($condition['gteq']) ? '>=' : '<=';
                    $value = $condition['gteq'] ?? $condition['lteq'];
                    $this->getSelect()
                        ->where("main_table.details->>'$.nextChargeDate' {$operator} '{$value}'");
                    break;
                case 'last_charge_date':
                    $operator = isset($condition['gteq']) ? '>=' : '<=';
                    $value = $condition['gteq'] ?? $condition['lteq'];
                    $this->getSelect()
                        ->where("main_table.details->>'$.lastChargeDate' {$operator} '{$value}'");
                    break;
                case 'title':
                    $this->getSelect()
                        ->where("main_table.details->>'$.title' LIKE '{$condition['like']}'");
                    break;
                case 'gateway_type':
                    $this->getSelect()
                        ->where("main_table.details->>'$.price.gateway.type' LIKE '{$condition['like']}'");
                    break;
                case 'gateway_description':
                    $this->getSelect()
                        ->where("main_table.details->>'$.price.gateway.description' LIKE '{$condition['like']}'");
                    break;
                case 'user_email':
                    $this->getSelect()
                        ->where("main_table.details->>'$.userEmail' LIKE '{$condition['like']}'");
                    break;
                default:
                    parent::addFieldToFilter($field, $condition);
                    break;
            }
            return $this;
        }
        if (isset($aliasFilters[$field])) {
            $field = $aliasFilters[$field];
        }
        return parent::addFieldToFilter($field, $condition);
    }
}
