<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2022 Improntus (http://www.improntus.com/)
 * @package Improntus_Rebill
 */

namespace Improntus\Rebill\Block\Adminhtml\Subscription;

use Magento\Backend\Block\Widget\Grid;

/**
 * @description We decided to use this method to print grids on the admin because of the flexibility to show data that comes from web service
 */
class Container extends Grid\Container
{
    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_controller = 'adminhtml_subscription_container';
        $this->_blockGroup = 'Improntus_Rebill';
        $this->_headerText = __('Rebill Subscriptions');
        parent::_construct();
        $this->removeButton('add');
    }

    /**
     * @return $this|Container|Grid\Container
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        $this->getChildBlock('grid');
        return $this;
    }
}
