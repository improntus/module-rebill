<?php

namespace Improntus\Rebill\Controller\Product;

use Improntus\Rebill\Helper\Config;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;

class Labels extends Action implements HttpPostActionInterface
{
    protected $collectionFactory;
    protected $configHelper;
    protected $jsonFactory;

    public function __construct(
        Context $context,
        Config $configHelper,
        CollectionFactory $collectionFactory,
        JsonFactory $jsonFactory
    ) {
        $this->jsonFactory = $jsonFactory;
        $this->configHelper = $configHelper;
        $this->collectionFactory = $collectionFactory;
        parent::__construct($context);
    }

    public function execute()
    {
        $ids = $this->getRequest()->getParam('ids');
        /** @var Collection $productCollection */
        $productCollection = $this->collectionFactory->create();
        $productCollection->addFieldToFilter('entity_id', ['in' => $ids]);
        $productCollection->addAttributeToSelect('*');
        $rebillItems = [];
        /** @var Product $product */
        foreach ($productCollection as $product) {
            $rebillDetails = $this->configHelper->getProductRebillSubscriptionDetails($product);
            if ($rebillDetails['enable_subscription']) {
                $rebillItems[$product->getId()] = true;
            }
        }
        $response = $this->jsonFactory->create();
        return $response->setData(['products' => $rebillItems]);
    }
}
