<?php

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
    protected $attributeCollectionFactory;
    protected $jsonHelper;
    protected $registry;


    public function __construct(
        Template\Context           $context,
        ProductMetadataInterface   $productMetadata,
        Registry                   $registry,
        Config                     $configHelper,
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

    public function getProductType()
    {
        /** @var Product|null $product */
        if ($product = $this->registry->registry('current_product')) {
            return $product->getData('type_id');
        } else {
            return $this->getRequest()->getParam('type');
        }
    }

    public function getProduct()
    {
        /** @var Product|null $product */
        return $this->registry->registry('current_product');
    }

    protected function getRebillAttributes(?string $code = null)
    {
        $attributes = [
            'rebill_enable_subscription'           => __('Add the option to subscribe to this product'),
            'rebill_inherit_from_parent'           => __('Inherit all the rebill settings from configurable product'),
            'rebill_individual_settings_in_simple' => __('Invalidate the settings in the configurable product allowing you to elaborate subscription combinations through configurable attributes and simple products'),
            'rebill_initial_subscription_cost'     => __('Cost for subscribe to this product. It will be billed only once'),
            'rebill_free_trial_time_lapse'         => __('Time lapse of free trial expressed in days. Once it\'s finished, it will be billed for the first time'),
            'rebill_frequency'                     => __('Frequency in which the subscription will be billed, expressed in months or years'),
//            'rebill_frequency_type'                => __('Expression of time for the frequency in which the product will be billed: months or years'),
//            'rebill_max_number_recurrent_payments' => __('Maximum number of payments per subscription. Example: if the frequency is 1 month and this value is 12, then the subscription will only be billed 12 times, each time after a month since the last payment, for a total subscription of a year'),
            'rebill_gateway_id'                    => __('Payment gateway through which the product will be charged'),
        ];
        if ($code) {
            $result = $attributes[$code];
        } else {
            $result = array_keys($attributes);
        }
        return $result;
    }

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
