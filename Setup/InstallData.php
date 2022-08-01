<?php

namespace Improntus\Rebill\Setup;

use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Eav\Setup\EavSetup;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Catalog\Model\Product\Attribute\Backend\Boolean as BackendBoolean;
use Magento\Eav\Model\Entity\Attribute\Source\Boolean as SourceBoolean;
use Improntus\Rebill\Model\Product\Attribute\Source\SubscriptionType as SourceSubscriptionType;
use Improntus\Rebill\Model\Product\Attribute\Source\Gateway as SourceGateway;

class InstallData implements InstallDataInterface
{
    /**
     * @var EavSetupFactory
     */
    protected $_eavSetupFactory;

    /**
     * @param EavSetupFactory $eavSetupFactory
     */
    public function __construct(
        EavSetupFactory $eavSetupFactory
    ) {
        $this->_eavSetupFactory = $eavSetupFactory;
    }

    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();
        /** @var EavSetup $eavSetup */
        $eavSetup = $this->_eavSetupFactory->create(['setup' => $setup]);

        $defaultOptions = [
            'backend'                 => null,
            'frontend'                => null,
            'source'                  => null,
            'global'                  => ScopedAttributeInterface::SCOPE_GLOBAL,
            'visible'                 => true,
            'required'                => false,
            'user_defined'            => false,
            'apply_to'                => 'simple,configurable',
            'group'                   => 'Rebill',
            'visible_on_front'        => false,
            'is_visible_in_grid'      => true,
            'is_filterable_in_grid'   => true,
            'used_in_product_listing' => true,
        ];

        $attributes = [
            'rebill_subscription_type'             => [
                'type'    => 'varchar',
                'input'   => 'select',
                'label'   => 'Subscription Type',
                'backend' => null,
                'source'  => SourceSubscriptionType::class,
                'default' => null,
            ],
            'rebill_inherit_from_parent'           => [
                'type'     => 'int',
                'input'    => 'boolean',
                'label'    => 'Inherit subscription options from parent',
                'apply_to' => 'simple',
                'backend'  => BackendBoolean::class,
                'source'   => SourceBoolean::class,
                'default'  => 0,
            ],
            'rebill_individual_settings_in_simple' => [
                'type'     => 'int',
                'input'    => 'boolean',
                'label'    => 'Individual settings in simple products',
                'apply_to' => 'configurable',
                'backend'  => BackendBoolean::class,
                'source'   => SourceBoolean::class,
                'default'  => 0,
            ],
            'rebill_gateway_id'                    => [
                'type'    => 'varchar',
                'input'   => 'select',
                'label'   => 'Gateway',
                'backend' => null,
                'source'  => SourceGateway::class,
                'default' => null,
            ],
            'rebill_free_trial_time_lapse'         => [
                'type'    => 'int',
                'input'   => 'text',
                'label'   => 'Free trial time (days)',
                'backend' => null,
                'source'  => null,
                'default' => 0,
            ],
            'rebill_frequency'                     => [
                'type'    => 'text',
                'input'   => 'text',
                'label'   => 'Frequency',
                'backend' => null,
                'source'  => null,
                'default' => null,
            ],
        ];

        foreach ($attributes as $attribute => $options) {
            $_options = array_merge($defaultOptions, $options);
            $eavSetup->addAttribute(Product::ENTITY, $attribute, $_options);
        }
        $setup->endSetup();
    }
}
