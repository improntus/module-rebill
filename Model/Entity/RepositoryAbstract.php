<?php

namespace Improntus\Rebill\Model\Entity;

use Exception;
use Improntus\Rebill\Model\Api\SearchCriteria\CollectionProcessor;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResults;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\EntityManager\HydratorInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class RepositoryAbstract
{
    /**
     * @var SearchCriteriaBuilder
     */
    protected SearchCriteriaBuilder $searchCriteriaBuilder;

    /**
     * @var string
     */
    private string $modelClass;

    /**
     * @var string
     */
    private string $resourceModelClass;

    /**
     * @var string
     */
    private string $collectionClass;

    /**
     * @var string
     */
    private string $searchResultsClass;

    /**
     * @var HydratorInterface|null
     */
    protected ?HydratorInterface $hydrator;

    /**
     * @var CollectionProcessorInterface
     */
    protected CollectionProcessorInterface $collectionProcessor;

    /**
     * @param string $modelClass
     * @param string $resourceModelClass
     * @param string $collectionClass
     * @param string $searchResultsClass
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param CollectionProcessorInterface|null $collectionProcessor
     * @param HydratorInterface|null $hydrator
     */
    public function __construct(
        string                       $modelClass,
        string                       $resourceModelClass,
        string                       $collectionClass,
        string                       $searchResultsClass,
        SearchCriteriaBuilder        $searchCriteriaBuilder,
        CollectionProcessorInterface $collectionProcessor = null,
        ?HydratorInterface           $hydrator = null
    ) {
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->modelClass = $modelClass;
        $this->resourceModelClass = $resourceModelClass;
        $this->collectionClass = $collectionClass;
        $this->searchResultsClass = $searchResultsClass;
        $this->hydrator = $hydrator;
        $this->collectionProcessor = $collectionProcessor ?: $this->getCollectionProcessor();
    }

    /**
     * @param AbstractModel $item
     * @return mixed
     * @throws CouldNotSaveException
     */
    public function save($item)
    {
        if ($item->getId() && $item instanceof $this->modelClass && !$item->getOrigData()) {
            $item = $this->hydrator->hydrate(
                $this->getById($item->getId()),
                $this->hydrator->extract($item)
            );
        }
        try {
            $this->getResourceModel()->save($item);
        } catch (Exception $exception) {
            throw new CouldNotSaveException(__($exception->getMessage()));
        }
        return $item;
    }

    /**
     * @param int|null $id
     * @return mixed
     */
    public function getById(?int $id)
    {
        $item = $this->create();
        if ($id) {
            $item->load($id);
        }
        return $item;
    }

    /**
     * @param array $data
     * @return mixed
     */
    protected function create(array $data = [])
    {
        return $this->getModel($data);
    }

    /**
     * @param SearchCriteriaInterface $searchCriteria
     * @return mixed
     */
    public function getList(SearchCriteriaInterface $searchCriteria)
    {
        $collection = $this->getCollection();
        $this->collectionProcessor->process($searchCriteria, $collection);
        $searchResults = $this->getSearchResult();
        $searchResults->setSearchCriteria($searchCriteria);
        /** @var array $items */
        $items = $collection->getItems();
        $searchResults->setItems($items);
        $searchResults->setTotalCount($collection->getSize());
        return $searchResults;
    }

    /**
     * @param array $filters
     * @return mixed
     */
    public function getEzList(array $filters = [])
    {
        foreach ($filters as $field => $condition) {
            if (is_array($condition)) {
                foreach ($condition as $conditionType => $value) {
                    $this->searchCriteriaBuilder->addFilter($field, $value, $conditionType);
                }
            } else {
                $this->searchCriteriaBuilder->addFilter($field, $condition);
            }
        }
        return $this->getList($this->searchCriteriaBuilder->create());
    }

    /**
     * @param mixed $item
     * @return void
     * @throws CouldNotDeleteException
     */
    public function delete($item): void
    {
        try {
            $this->getResourceModel()->delete($item);
        } catch (Exception $exception) {
            throw new CouldNotDeleteException(__($exception->getMessage()));
        }
    }

    /**
     * @param int $itemId
     * @return void
     * @throws CouldNotDeleteException
     */
    public function deleteById(int $itemId): void
    {
        $this->delete($this->getById($itemId));
    }

    /**
     * @return SearchResults
     */
    protected function getSearchResult()
    {
        return $this->getObject($this->searchResultsClass);
    }

    /**
     * @param array $data
     * @return AbstractModel
     */
    private function getModel(array $data = [])
    {
        return $this->getObject($this->modelClass, $data);
    }

    /**
     * @return AbstractDb
     */
    protected function getResourceModel()
    {
        return $this->getObject($this->resourceModelClass);
    }

    /**
     * @return AbstractCollection
     */
    public function getCollection()
    {
        return $this->getObject($this->collectionClass);
    }

    /**
     * @param string $class
     * @param array $data
     * @return mixed
     */
    private function getObject(string $class, array $data = [])
    {
        return ObjectManager::getInstance()->create($class, ['data' => $data]);
    }

    /**
     * @return CollectionProcessor
     */
    private function getCollectionProcessor(): CollectionProcessor
    {
        return ObjectManager::getInstance()->get(CollectionProcessor::class);
    }
}
