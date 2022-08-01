<?php
namespace Improntus\Rebill\Block\Adminhtml\Subscription;

use Magento\Backend\Block\Widget\Grid;

class Container extends Grid\Container
{
    protected function _construct()
    {
        $this->_controller = 'adminhtml_subscription_container';
        $this->_blockGroup = 'Improntus_Rebill';
        $this->_headerText = __('Rebill Subscriptions');
        parent::_construct();
        $this->removeButton('add');
    }

    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        /** @var Grid $gridBlock */
        $gridBlock = $this->getChildBlock('grid');
        return $this;
    }
}
