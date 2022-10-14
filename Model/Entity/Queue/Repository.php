<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2022 Improntus (http://www.improntus.com/)
 * @package Improntus_Rebill
 */

namespace Improntus\Rebill\Model\Entity\Queue;

use Improntus\Rebill\Api\Queue\DataInterface;
use Improntus\Rebill\Api\Queue\RepositoryInterface;
use Improntus\Rebill\Api\Queue\SearchResultInterface;
use Improntus\Rebill\Model\Entity\RepositoryAbstract;
use Improntus\Rebill\Model\Rebill\Item as RebillItem;
use Improntus\Rebill\Model\ResourceModel\Queue;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\EntityManager\HydratorInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Improntus\Rebill\Model\Entity\Item\Repository as ItemRepository;

class Repository extends RepositoryAbstract implements RepositoryInterface
{
    /**
     * @var RebillItem
     */
    protected $rebillItem;

    /**
     * @var ItemRepository
     */
    protected $itemRepository;

    /**
     * @param RebillItem $rebillItem
     * @param ItemRepository $itemRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param CollectionProcessorInterface|null $collectionProcessor
     * @param HydratorInterface|null $hydrator
     */
    public function __construct(
        RebillItem                   $rebillItem,
        ItemRepository               $itemRepository,
        SearchCriteriaBuilder        $searchCriteriaBuilder,
        CollectionProcessorInterface $collectionProcessor = null,
        ?HydratorInterface           $hydrator = null
    ) {
        $this->rebillItem = $rebillItem;
        $this->itemRepository = $itemRepository;
        parent::__construct(
            Model::class,
            Queue::class,
            Queue\Collection::class,
            SearchResults::class,
            $searchCriteriaBuilder,
            $collectionProcessor,
            $hydrator
        );
    }

    /**
     * @return Queue\Collection
     */
    public function getCollection()
    {
        $result = parent::getCollection();
        return $result instanceof Queue\Collection ? $result : null;
    }

    /**
     * @param int|null $id
     * @return DataInterface
     */
    public function getById(?int $id)
    {
        $result = parent::getById($id);
        return $result instanceof DataInterface ? $result : null;
    }

    /**
     * @param int $id
     * @param string $status
     * @return bool
     */
    public function validateStatus(int $id, string $status)
    {
        $result = $this->getById($id);
        return $result->getStatus() == $status;
    }

    /**
     * @param SearchCriteriaInterface $searchCriteria
     * @return SearchResultInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria)
    {
        $result = parent::getList($searchCriteria);
        return $result instanceof SearchResultInterface ? $result : null;
    }

    /**
     * @param array $filters
     * @param int|null $pageSize
     * @return SearchResultInterface
     */
    public function getEzList(array $filters = [], ?int $pageSize = null)
    {
        $result = parent::getEzList($filters, $pageSize);
        return $result instanceof SearchResultInterface ? $result : null;
    }

    /**
     * @param DataInterface $item
     * @return DataInterface
     * @throws CouldNotSaveException
     */
    public function save($item)
    {
        if ($item instanceof DataInterface) {
            return parent::save($item);
        }
        return $item;
    }

    /**
     * @param array $data
     * @return DataInterface
     */
    public function create(array $data = [])
    {
        $result = parent::create($data);
        return $result instanceof DataInterface ? $result : null;
    }

    /**
     * @param DataInterface $item
     * @return void
     * @throws CouldNotDeleteException
     */
    public function delete($item): void
    {
        if ($item instanceof DataInterface) {
            parent::delete($item);
        }
    }
}
