<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2022 Improntus (http://www.improntus.com/)
 * @package Improntus_Rebill
 */

namespace Improntus\Rebill\Model\Entity\Item;

use Improntus\Rebill\Api\Item\DataInterface;
use Improntus\Rebill\Api\Item\RepositoryInterface;
use Improntus\Rebill\Api\Item\SearchResultInterface;
use Improntus\Rebill\Helper\Config;
use Improntus\Rebill\Model\Entity\RepositoryAbstract;
use Improntus\Rebill\Model\ResourceModel\Item;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\EntityManager\HydratorInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Improntus\Rebill\Model\Rebill\Item as RebillItem;

class Repository extends RepositoryAbstract implements RepositoryInterface
{
    /**
     * @var RebillItem
     */
    protected $rebillItem;

    /**
     * @var Config
     */
    protected $configHelper;

    /**
     * @param RebillItem $rebillItem
     * @param Config $configHelper
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param CollectionProcessorInterface|null $collectionProcessor
     * @param HydratorInterface|null $hydrator
     */
    public function __construct(
        RebillItem                   $rebillItem,
        Config                       $configHelper,
        SearchCriteriaBuilder        $searchCriteriaBuilder,
        CollectionProcessorInterface $collectionProcessor = null,
        ?HydratorInterface           $hydrator = null
    ) {
        $this->rebillItem = $rebillItem;
        $this->configHelper = $configHelper;
        parent::__construct(
            Model::class,
            Item::class,
            Item\Collection::class,
            SearchResults::class,
            $searchCriteriaBuilder,
            $collectionProcessor,
            $hydrator
        );
    }

    /**
     * @return Item\Collection
     */
    public function getCollection()
    {
        $result = parent::getCollection();
        return $result instanceof Item\Collection ? $result : null;
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
     * @param string $sku
     * @return DataInterface
     */
    public function getBySku(string $sku)
    {
        $item = $this->create();
        $data = [$sku, $this->configHelper->getApiUuid()];
        $hash = hash('md5', implode('-', $data));
        $this->getResourceModel()->load($item, $hash, 'hash');
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
            if (!$item->getRebillId()) {
                $rebillItem = $this->rebillItem->createItem([
                    'name'        => $this->configHelper->getShortDescription($item->getSku(), 50),
                    'description' => $this->configHelper->getShortDescription($item->getDescription(), 150),
                ]);
                if ($rebillItem) {
                    $item->setRebillId($rebillItem);
                } else {
                    return $item;
                }
            }
            if (!$item->getHash()) {
                $data = [
                    $item->getSku(),
                    $this->configHelper->getApiUuid(),
                ];
                $hash = hash('md5', implode('-', $data));
                $item->setHash($hash);
            }
            return parent::save($item);
        }
        return $item;
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
