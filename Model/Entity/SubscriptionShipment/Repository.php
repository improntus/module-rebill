<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2022 Improntus (http://www.improntus.com/)
 * @package Improntus_Rebill
 */

namespace Improntus\Rebill\Model\Entity\SubscriptionShipment;

use Improntus\Rebill\Api\SubscriptionShipment\DataInterface;
use Improntus\Rebill\Api\SubscriptionShipment\RepositoryInterface;
use Improntus\Rebill\Api\SubscriptionShipment\SearchResultInterface;
use Improntus\Rebill\Model\Entity\RepositoryAbstract;
use Improntus\Rebill\Model\ResourceModel\SubscriptionShipment;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\EntityManager\HydratorInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;

class Repository extends RepositoryAbstract implements RepositoryInterface
{
    /**
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param CollectionProcessorInterface|null $collectionProcessor
     * @param HydratorInterface|null $hydrator
     */
    public function __construct(
        SearchCriteriaBuilder        $searchCriteriaBuilder,
        CollectionProcessorInterface $collectionProcessor = null,
        ?HydratorInterface           $hydrator = null
    ) {
        parent::__construct(
            Model::class,
            SubscriptionShipment::class,
            SubscriptionShipment\Collection::class,
            SearchResults::class,
            $searchCriteriaBuilder,
            $collectionProcessor,
            $hydrator
        );
    }

    /**
     * @return SubscriptionShipment\Collection
     */
    public function getCollection()
    {
        $result = parent::getCollection();
        return $result instanceof SubscriptionShipment\Collection ? $result : null;
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
     * @param string $id
     * @return DataInterface|null
     */
    public function getByRebillId(string $id)
    {
        $price = $this->create();
        $this->getResourceModel()->load($price, $id, 'rebill_id');
        return $price;
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
        return null;
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
