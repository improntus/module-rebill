<?php
/*
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2022 Improntus (http://www.improntus.com/)
 * @package Improntus_Rebill
 */

namespace Improntus\Rebill\Model\Grid;

use Improntus\Rebill\Model\ResourceModel\Queue as Model;
use Magento\Framework\Api\ExtensionAttribute\JoinDataInterfaceFactory;
use Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface;
use Magento\Framework\Data\Collection\Db\FetchStrategyInterface as FetchStrategy;
use Magento\Framework\Data\Collection\EntityFactoryInterface as EntityFactory;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult;
use Psr\Log\LoggerInterface as Logger;
use Zend_Db_Expr;

class Queue extends SearchResult
{
    /**
     * @var JoinDataInterfaceFactory
     */
    protected $joinData;

    /**
     * @var JoinProcessorInterface
     */
    protected $joinProcessor;

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
        $mainTable = 'rebill_queue';
        $resourceModel = Model::class;
        parent::__construct($entityFactory, $logger, $fetchStrategy, $eventManager, $mainTable, $resourceModel);
    }

    /**
     * @return $this|Queue|void
     */
    protected function _initSelect()
    {
        parent::_initSelect();
        $this->getSelect()->where('main_table.status IN ("pending", "failed")');
        return $this;
    }
}
