<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2022 Improntus (http://www.improntus.com/)
 * @package Improntus_Rebill
 */

namespace Improntus\Rebill\Block\Adminhtml;

use Improntus\Rebill\Helper\Config;
use Magento\Backend\Block\Template;
use Magento\Catalog\Model\Entity\Attribute;
use Magento\Catalog\Model\Product;
use Magento\Directory\Helper\Data as DirectoryHelper;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Json\Helper\Data as JsonHelper;
use Magento\Framework\Registry;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory as AttributeCollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection as AttributeCollection;

class ProductForm extends Template
{
    /**
     * @var AttributeCollectionFactory
     */
    protected $attributeCollectionFactory;

    /**
     * @var JsonHelper
     */
    protected $jsonHelper;

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @param Template\Context $context
     * @param ProductMetadataInterface $productMetadata
     * @param Registry $registry
     * @param AttributeCollectionFactory $attributeCollectionFactory
     * @param JsonHelper $jsonHelper
     * @param array $data
     * @param DirectoryHelper|null $directoryHelper
     */
    public function __construct(
        Template\Context           $context,
        ProductMetadataInterface   $productMetadata,
        Registry                   $registry,
        AttributeCollectionFactory $attributeCollectionFactory,
        JsonHelper                 $jsonHelper,
        array                      $data = [],
        ?DirectoryHelper           $directoryHelper = null
    ) {
        $this->registry = $registry;
        $this->jsonHelper = $jsonHelper;
        $this->attributeCollectionFactory = $attributeCollectionFactory;
        if (version_compare($productMetadata->getVersion(), '2.4', '>=')) {
            parent::__construct($context, $data, $jsonHelper, $directoryHelper);
        } else {
            parent::__construct($context, $data);
        }
    }

    /**
     * @return mixed|null
     * @description Return product type to show frequency options
     */
    public function getProductType()
    {
        /** @var Product|null $product */
        if ($product = $this->registry->registry('current_product')) {
            return $product->getData('type_id');
        } else {
            return $this->getRequest()->getParam('type');
        }
    }

    /**
     * @return mixed|null
     * @description returns current product in product admin form
     */
    public function getProduct()
    {
        /** @var Product|null $product */
        return $this->registry->registry('current_product');
    }

    /**
     * @param string|null $code
     * @return int[]|mixed|string[]
     * @description return attributes with its tooltips
     */
    protected function getRebillAttributes(?string $code = null)
    {
        $attributes = [
            'rebill_enable_subscription'       => __('Add the option to subscribe to this product'),
            'rebill_free_trial_time_lapse'     => __('Time lapse of free trial expressed in days. Once it\'s finished, it will be billed for the first time'),
            'rebill_frequency'                 => __('Frequency in which the subscription will be billed, expressed in months or years'),
            'rebill_gateway_id'                => __('Payment gateway through which the product will be charged'),
        ];
        if ($code) {
            $result = $attributes[$code];
        } else {
            $result = array_keys($attributes);
        }
        return $result;
    }

    /**
     * @return string
     * @description return attributes in a json array to process the data in the js
     */
    public function getRebillAttributesJson()
    {
        /** @var AttributeCollection $collection */
        $collection = $this->attributeCollectionFactory->create();
        $collection->addFieldToFilter('attribute_code', ['in' => $this->getRebillAttributes()]);
        $attributes = [];
        /** @var Attribute $attribute */
        foreach ($collection as $attribute) {
            $_attribute = [
                'code'     => $attribute->getAttributeCode(),
                'apply_to' => explode(',', $attribute->getData('apply_to')),
                'tooltip'  => $this->getRebillAttributes($attribute->getAttributeCode()),
            ];
            if ($attribute->getAttributeCode() == 'rebill_frequency') {
                $product = $this->registry->registry('current_product');
                $_attribute['value'] = $product ? json_decode($product->getData('rebill_frequency') ?: '[]', true) : [];
            }
            $attributes[] = $_attribute;
        }
        return $this->jsonHelper->jsonEncode($attributes);
    }
}
