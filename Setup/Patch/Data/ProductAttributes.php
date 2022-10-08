<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2022 Improntus (http://www.improntus.com/)
 * @package Improntus_Rebill
 */

namespace Improntus\Rebill\Setup\Patch\Data;

use Improntus\Rebill\Model\Product\Attribute\Source\SubscriptionType as SourceSubscriptionType;
use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;
use Zend_Validate_Exception;

class ProductAttributes implements DataPatchInterface, PatchRevertableInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    protected $moduleDataSetup;

    /**
     * @var EavSetupFactory
     */
    protected $eavSetupFactory;

    /**
     * @var array[]
     */
    protected $attributes = [
        'rebill_subscription_type'     => [
            'type'    => 'varchar',
            'input'   => 'select',
            'label'   => 'Subscription Type',
            'backend' => null,
            'source'  => SourceSubscriptionType::class,
            'default' => null,
        ],
        'rebill_frequency'             => [
            'type'    => 'text',
            'input'   => 'text',
            'label'   => 'Frequency',
            'backend' => null,
            'source'  => null,
            'default' => null,
        ],
    ];

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param EavSetupFactory $eavSetupFactory
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        EavSetupFactory          $eavSetupFactory
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->eavSetupFactory = $eavSetupFactory;
    }

    /**
     * @return void
     * @throws LocalizedException
     * @throws Zend_Validate_Exception
     */
    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);
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
        foreach ($this->attributes as $attribute => $options) {
            $_options = $defaultOptions;
            foreach ($options as $k => $v) {
                $_options[$k] = $v;
            }
            $eavSetup->addAttribute(Product::ENTITY, $attribute, $_options);
        }
        $this->moduleDataSetup->getConnection()->endSetup();
    }

    /**
     * @return void
     */
    public function revert()
    {
        $this->moduleDataSetup->getConnection()->startSetup();
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);
        foreach (array_keys($this->attributes) as $attribute) {
            $eavSetup->removeAttribute(Product::ENTITY, $attribute);
        }
        $this->moduleDataSetup->getConnection()->endSetup();
    }

    /**
     * @return array|string[]
     */
    public function getAliases()
    {
        return [];
    }

    /**
     * @return array|string[]
     */
    public static function getDependencies()
    {
        return [];
    }
}
