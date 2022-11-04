<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2022 Improntus (http://www.improntus.com/)
 * @package Improntus_Rebill
 */

namespace Improntus\Rebill\Model\Entity\Subscription;

use Exception;
use Improntus\Rebill\Api\Subscription\DataInterface;
use Improntus\Rebill\Api\Subscription\RepositoryInterface;
use Improntus\Rebill\Api\Subscription\SearchResultInterface;
use Improntus\Rebill\Model\Entity\RepositoryAbstract;
use Improntus\Rebill\Model\ResourceModel\Subscription;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\EntityManager\HydratorInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Improntus\Rebill\Model\Rebill\Subscription as RebillSubscription;
use Improntus\Rebill\Model\Entity\SubscriptionShipment\Repository as SubscriptionShipmentRepository;
use Improntus\Rebill\Model\Entity\Price\Repository as PriceRepository;

class Repository extends RepositoryAbstract implements RepositoryInterface
{
    /**
     * @var RebillSubscription
     */
    protected $rebillSubscription;

    /**
     * @var SubscriptionShipmentRepository
     */
    protected $subscriptionShipmentRepository;

    /**
     * @var PriceRepository
     */
    protected $priceRepository;

    /**
     * @param SubscriptionShipmentRepository $subscriptionShipmentRepository
     * @param PriceRepository $priceRepository
     * @param RebillSubscription $rebillSubscription
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param CollectionProcessorInterface|null $collectionProcessor
     * @param HydratorInterface|null $hydrator
     */
    public function __construct(
        SubscriptionShipmentRepository $subscriptionShipmentRepository,
        PriceRepository                $priceRepository,
        RebillSubscription             $rebillSubscription,
        SearchCriteriaBuilder          $searchCriteriaBuilder,
        CollectionProcessorInterface   $collectionProcessor = null,
        ?HydratorInterface             $hydrator = null
    ) {
        $this->priceRepository = $priceRepository;
        $this->subscriptionShipmentRepository = $subscriptionShipmentRepository;
        $this->rebillSubscription = $rebillSubscription;
        parent::__construct(
            Model::class,
            Subscription::class,
            Subscription\Collection::class,
            SearchResults::class,
            $searchCriteriaBuilder,
            $collectionProcessor,
            $hydrator
        );
    }

    /**
     * @return SubscriptionShipmentRepository
     */
    public function getSubscriptionShipmentRepository()
    {
        return $this->subscriptionShipmentRepository;
    }

    /**
     * @return Subscription\Collection
     */
    public function getCollection()
    {
        $result = parent::getCollection();
        return $result instanceof Subscription\Collection ? $result : null;
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

    /**
     * @param string $id
     * @return mixed|null
     * @throws Exception
     */
    public function getInvoiceById(string $id)
    {
        return $this->rebillSubscription->getInvoice($id);
    }

    /**
     * @param string $id
     * @param string $email
     * @return mixed|null
     * @throws Exception
     */
    public function getRebillSubscription(string $id, string $email)
    {
        return $this->rebillSubscription->getSubscription($id, $email);
    }

    /**
     * @param string $rebillId
     * @param bool $isHash
     * @return array
     * @throws Exception
     */
    public function getSubscriptionPackage(string $rebillId, bool $isHash = false)
    {
        $filters = [];
        if (!$isHash) {
            $subscription = $this->getByRebillId($rebillId);
            if (!$subscription->getId()) {
                $shipment = $this->subscriptionShipmentRepository->getByRebillId($rebillId);
                if (!$shipment->getId()) {
                    throw new Exception(__('No subscription found'));
                }
                $filters['shipment_id'] = $shipment->getId();
            } else {
                $filters['package_hash'] = $subscription->getPackageHash();
                $shipment = $this->subscriptionShipmentRepository->getById($subscription->getShipmentId());
            }
        } else {
            $filters['package_hash'] = $rebillId;
        }
        $subscriptionList = $this->getEzList($filters)->getItems();
        foreach ($subscriptionList as &$item) {
            $price = $this->priceRepository->getByRebillId($item->getRebillPriceId());
            $item->setData('price', $price);
            if (!isset($shipment)) {
                $shipment = $this->subscriptionShipmentRepository->getById($item->getShipmentId());
            }
        }
        if (!isset($shipment) || !$shipment->getId()) {
            $shipment = null;
        }
        return [
            'subscription'      => isset($subscription) ? ($subscription->getId() ? $subscription : $shipment) : null,
            'subscription_list' => $subscriptionList,
            'shipment'          => $shipment,
        ];
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
     * @param int|null $id
     * @return DataInterface
     */
    public function getById(?int $id)
    {
        $result = parent::getById($id);
        return $result instanceof DataInterface ? $result : null;
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
}
