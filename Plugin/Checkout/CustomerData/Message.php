<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2022 Improntus (http://www.improntus.com/)
 * @package Improntus_Rebill
 */

namespace Improntus\Rebill\Plugin\Checkout\CustomerData;

use Improntus\Rebill\Helper\Config;
use Magento\Checkout\CustomerData\Cart;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Message\ManagerInterface;

class Message
{
    /**
     * @var Config
     */
    protected $configHelper;

    /**
     * @var ManagerInterface
     */
    protected $messageManager;

    /**
     * @param Config $configHelper
     */
    public function __construct(
        ManagerInterface $messageManager,
        Config $configHelper
    ) {
        $this->messageManager = $messageManager;
        $this->configHelper = $configHelper;
    }

    /**
     * Add dynamic message to cart section data
     *
     * @param Cart $subject
     * @param array $result
     * @return array
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetSectionData(Cart $subject, array $result): array
    {
        if ($this->configHelper->checkoutHasMixedCartConflict()) {
            $message = $this->configHelper->getCheckOutMixedCartConflictMessage();
            $result['message'] = $message;
            $this->messageManager->addErrorMessage($message);
        }
        return $result;
    }
}
