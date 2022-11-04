<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2022 Improntus (http://www.improntus.com/)
 * @package Improntus_Rebill
 */

namespace Improntus\Rebill\Controller\Product;

use Improntus\Rebill\Helper\Config;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;

class Labels extends Action implements HttpPostActionInterface
{
    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var Config
     */
    protected $configHelper;

    /**
     * @var JsonFactory
     */
    protected $jsonFactory;

    /**
     * @param Context $context
     * @param Config $configHelper
     * @param CollectionFactory $collectionFactory
     * @param JsonFactory $jsonFactory
     */
    public function __construct(
        Context           $context,
        Config            $configHelper,
        CollectionFactory $collectionFactory,
        JsonFactory       $jsonFactory
    ) {
        $this->jsonFactory = $jsonFactory;
        $this->configHelper = $configHelper;
        $this->collectionFactory = $collectionFactory;
        parent::__construct($context);
    }

    /**
     * @return ResponseInterface|Json|ResultInterface
     */
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
            if (isset($rebillDetails['enable_subscription']) && $rebillDetails['enable_subscription']) {
                $rebillItems[$product->getId()] = true;
            }
        }
        $response = $this->jsonFactory->create();
        return $response->setData(['products' => $rebillItems]);
    }
}
