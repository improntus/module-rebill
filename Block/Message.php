<?php

namespace Improntus\Rebill\Block;

use Improntus\Rebill\Helper\Config;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Phrase;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

class Message extends Template
{
    /**
     * @var Config
     */
    protected $configHelper;

    /**
     * @param Context $context
     * @param Config $configHelper
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        Config $configHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->configHelper = $configHelper;
    }

    /**
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function getMessage()
    {
        return $this->configHelper->checkoutHasMixedCartConflict()
            ? $this->configHelper->getCheckOutMixedCartConflictMessage()
            : '';
    }
}
