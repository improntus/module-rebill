<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2022 Improntus (http://www.improntus.com/)
 * @package Improntus_Rebill
 */

namespace Improntus\Rebill\Model\Entity\Price;

use Improntus\Rebill\Api\Price\DataInterface;
use Improntus\Rebill\Api\Price\RepositoryInterface;
use Improntus\Rebill\Api\Price\SearchResultInterface;
use Improntus\Rebill\Helper\Config;
use Improntus\Rebill\Model\Entity\RepositoryAbstract;
use Improntus\Rebill\Model\Rebill\Item as RebillItem;
use Improntus\Rebill\Model\ResourceModel\Price;
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
     * @var Config
     */
    protected $configHelper;

    /**
     * @param Config $configHelper
     * @param RebillItem $rebillItem
     * @param ItemRepository $itemRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param CollectionProcessorInterface|null $collectionProcessor
     * @param HydratorInterface|null $hydrator
     */
    public function __construct(
        Config                       $configHelper,
        RebillItem                   $rebillItem,
        ItemRepository               $itemRepository,
        SearchCriteriaBuilder        $searchCriteriaBuilder,
        CollectionProcessorInterface $collectionProcessor = null,
        ?HydratorInterface           $hydrator = null
    ) {
        $this->configHelper = $configHelper;
        $this->rebillItem = $rebillItem;
        $this->itemRepository = $itemRepository;
        parent::__construct(
            Model::class,
            Price::class,
            Price\Collection::class,
            SearchResults::class,
            $searchCriteriaBuilder,
            $collectionProcessor,
            $hydrator
        );
    }

    /**
     * @return Price\Collection
     */
    public function getCollection()
    {
        $result = parent::getCollection();
        return $result instanceof Price\Collection ? $result : null;
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
        $this->getResourceModel()->load($price, $id, 'rebill_price_id');
        return $price;
    }

    /**
     * @param string $hash
     * @return DataInterface
     */
    public function getByHash(string $hash)
    {
        $price = $this->create();
        $this->getResourceModel()->load($price, $hash, 'details_hash');
        return $price;
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
     * @param array $pricesIds
     * @return array
     */
    public function getPricesByFrequency(array $pricesIds)
    {
        $result = $this->getEzList(['rebill_price_id' => ['in' => $pricesIds]]);
        $prices = [];
        foreach ($result->getItems() as $item) {
            $prices[$item->getFrequencyHash()][] = $item;
        }
        return $prices;
    }

    /**
     * @param DataInterface $item
     * @return DataInterface
     * @throws CouldNotSaveException
     */
    public function save($item)
    {
        if ($item instanceof DataInterface) {
            if (!$item->getItemId()) {
                $_item = $this->itemRepository->getBySku($item->getDetails()['sku']);
                if (!$_item->getId()) {
                    $_item->setSku($item->getDetails()['sku']);
                    $_item->setDescription($item->getDetails()['product_name']);
                }
                $this->itemRepository->save($_item);
                if ($_item->getId() && $_item->getRebillId()) {
                    $item->setItemId($_item->getId());
                    $item->setRebillItemId($_item->getRebillId());
                } else {
                    return $item;
                }
            }
            if (!$item->getRebillPriceId()) {
                $rebillPrice = $this->rebillItem->createPriceForItem(
                    $item->getRebillItemId(),
                    $item->getRebillDetails()
                );
                if ($rebillPrice) {
                    $item->setRebillPriceId($rebillPrice);
                } else {
                    return $item;
                }
            } else {
                $this->rebillItem->updatePrice(
                    $item->getRebillPriceId(),
                    [
                        'amount'      => (string)$item->getDetails()['price'],
                        'type'        => 'fixed',
                        'repetitions' => $item->getDetails()['frequency']['recurring_payments'],
                        'currency'    => (string)$item->getDetails()['currency'],
                        'gatewayId'   => (string)$item->getDetails()['gateway'],
                    ]
                );
            }
            return parent::save($item);
        }
        return null;
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
