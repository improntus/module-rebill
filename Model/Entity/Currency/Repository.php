<?php

namespace Improntus\Rebill\Model\Entity\Currency;

use Improntus\Rebill\Api\Currency\DataInterface;
use Improntus\Rebill\Api\Currency\RepositoryInterface;
use Improntus\Rebill\Api\Currency\SearchResultInterface;
use Improntus\Rebill\Model\Entity\RepositoryAbstract;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\EntityManager\HydratorInterface;
use Improntus\Rebill\Model\ResourceModel\Currency;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;



class Repository extends RepositoryAbstract implements RepositoryInterface
{
    /**
     * @var string
     */
    protected $_eventPrefix = 'rebill_currency_model';

    /**
     * Initialize magento model.
     *
     * @return void
     */
    public function __construct(
        SearchCriteriaBuilder          $searchCriteriaBuilder,
        CollectionProcessorInterface   $collectionProcessor = null,
        ?HydratorInterface             $hydrator = null
    ) {

        parent::__construct(
            Model::class,
            Currency::class,
            Currency\Collection::class,
            SearchResults::class,
            $searchCriteriaBuilder,
            $collectionProcessor,
            $hydrator
        );
    }
    /**
     * @param int|null $id
     * @return \Improntus\Rebill\Api\Currency\DataInterface
     */
    public function getById(?int $id)
    {
        $result = parent::getById($id);
        return $result instanceof DataInterface ? $result : null;
    }
    /**
     * @return Currency\Collection
     */
    public function getCollection()
    {
        $result = parent::getCollection();
        return $result instanceof Currency\Collection ? $result : null;
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
     * @param \Improntus\Rebill\Api\Currency\DataInterface $item
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
     * @param \Improntus\Rebill\Api\Currency\DataInterface $item
     * @return void
     * @throws CouldNotDeleteException
     */
    public function delete($item): void
    {
        if ($item instanceof DataInterface) {
            parent::delete($item);
        }
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
}
