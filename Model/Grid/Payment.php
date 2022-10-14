<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2022 Improntus (http://www.improntus.com/)
 * @package Improntus_Rebill
 */

namespace Improntus\Rebill\Model\Grid;

use Zend_Db_Expr;
use Psr\Log\LoggerInterface as Logger;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Improntus\Rebill\Model\ResourceModel\Payment as PaymentModel;
use Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface;
use Magento\Framework\Api\ExtensionAttribute\JoinDataInterfaceFactory;
use Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult;
use Magento\Framework\Data\Collection\EntityFactoryInterface as EntityFactory;
use Magento\Framework\Data\Collection\Db\FetchStrategyInterface as FetchStrategy;

class Payment extends SearchResult
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
        $mainTable = 'rebill_payment';
        $resourceModel = PaymentModel::class;
        parent::__construct($entityFactory, $logger, $fetchStrategy, $eventManager, $mainTable, $resourceModel);
    }

    /**
     * @return Payment|void
     */
    protected function _initSelect()
    {
        parent::_initSelect();

        $columns = [
            /**
             * Main Table Columns
             */
            "item_id" => "JSON_UNQUOTE((JSON_EXTRACT(main_table.details, '$.id')))",
            "amount" => "JSON_UNQUOTE((JSON_EXTRACT(main_table.details, '$.amount')))",
            "currency" => "JSON_UNQUOTE((JSON_EXTRACT(main_table.details, '$.currency')))",
            "paymentId" => "JSON_UNQUOTE((JSON_EXTRACT(main_table.details, '$.paymentId')))",
            "description" => "JSON_UNQUOTE((JSON_EXTRACT(main_table.details, '$.description')))",
            "createdAt" => "DATE_FORMAT(JSON_UNQUOTE((JSON_EXTRACT(main_table.details, '$.createdAt'))),'%Y-%m-%d')",
            "card_last_number" => "JSON_UNQUOTE(JSON_EXTRACT(main_table.details, '$.card.last4'))",
            "card_brand" => "IFNULL(JSON_UNQUOTE(JSON_EXTRACT(main_table.details, '$.card.brand')),'')",
            "entity_id" => "main_table.entity_id",
            "status" => "main_table.status",
            "rebill_id" => "main_table.rebill_id",
            /**
             * Order Columns
             */
            "customer_fullname" => "CONCAT_WS(' ',o.customer_firstname,o.customer_middlename,o.customer_lastname)",
            "order_id" => "o.entity_id",
            "increment_id" => "o.increment_id",
            "customer_firstname" => "o.customer_firstname",
            "customer_lastname" => "o.customer_lastname",
            /**
             * Rebill Subscription Columns
             */
            "gateway_type" => "IFNULL(JSON_UNQUOTE(JSON_EXTRACT(main_table.details, '$.gateway.type')),'')",
            "gateway_description" => "IFNULL(JSON_UNQUOTE(JSON_EXTRACT(main_table.details, '$.gateway.description')),'')",
        ];

        // o => Sales Order
        $this->getSelect()->joinLeft(['o' => 'sales_order'], 'main_table.order_id = o.entity_id', []);

        $this->getSelect()->reset('columns');
        foreach ($columns as $alias => $column) {
            $this->addFieldToSelect(new Zend_Db_Expr("{$column} AS {$alias}"));
        }
        return $this;
    }

    public function addFieldToFilter($field, $condition = null)
    {
        $aliasFilters = [
            'order_id' => 'o.entity_id',
            'increment_id' => 'o.increment_id',
            'order_increment_id' => 'o.increment_id',
            'customer_firstname' => 'o.customer_firstname',
            'customer_lastname' => 'o.customer_lastname',
            'rebill_id' => 'main_table.rebill_id',
        ];
        $customFilters = [
            'customer_fullname',
            'gateway_type',
            'gateway_description',
            'createdAt',
            'card_last_number',
            'card_brand',
            'description',
            'paymentId',
            'amount'
        ];
        if (in_array($field, $customFilters)) {
            switch ($field) {
                case 'gateway_type':
                    $this->getSelect()
                        ->where("main_table.details->>'$.gateway.type' LIKE '{$condition['like']}'");
                    break;
                case 'gateway_description':
                    $this->getSelect()
                        ->where("main_table.details->>'$.gateway.description' LIKE '{$condition['like']}'");
                    break;
                case 'createdAt':
                    $operator = isset($condition['gteq']) ? '>=' : '<=';
                    $value = $condition['gteq'] ?? $condition['lteq'];
                    $this->getSelect()
                        ->where("main_table.details->>'$.createdAt' {$operator} '{$value}'");
                    break;
                case 'card_last_number':
                    $this->getSelect()
                        ->where("main_table.details->>'$.card.last4' LIKE '{$condition['like']}'");
                    break;
                case 'description':
                    $this->getSelect()
                        ->where("main_table.details->>'$.description' LIKE '{$condition['like']}'");
                    break;
                case 'card_brand':
                    $this->getSelect()
                        ->where("main_table.details->>'$.card.brand' LIKE '{$condition['like']}'");
                    break;
                case 'paymentId':
                    $this->getSelect()
                        ->where("main_table.details->>'$.paymentId' LIKE '{$condition['like']}'");
                    break;
                case 'amount':
                    $operator = isset($condition['gteq']) ? '>=' : '<=';
                    $value = $condition['gteq'] ?? $condition['lteq'];
                    $this->getSelect()
                        ->where("main_table.details->>'$.amount' {$operator} '{$value}'");
                    break;
                case 'customer_fullname':
                    $this->getSelect()
                        ->where("CONCAT_WS(' ',o.customer_firstname,o.customer_lastname) LIKE '{$condition['like']}'");
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
