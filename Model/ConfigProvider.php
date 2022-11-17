<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2022 Improntus (http://www.improntus.com/)
 * @package Improntus_Rebill
 */

namespace Improntus\Rebill\Model;

use Improntus\Rebill\Helper\Config;
use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Message\ManagerInterface;

class ConfigProvider implements ConfigProviderInterface
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
     * @param ManagerInterface $messageManager
     * @param Config $configHelper
     */
    public function __construct(
        ManagerInterface $messageManager,
        Config           $configHelper
    ) {
        $this->messageManager = $messageManager;
        $this->configHelper = $configHelper;
    }

    /**
     * @return array
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getConfig()
    {
        $message = '';
        if ($this->configHelper->checkoutHasMixedCartConflict()) {
            $message = $this->configHelper->getCheckOutMixedCartConflictMessage();
            $this->messageManager->addErrorMessage($message);
        }

        return [
            'summary_message' => $message
        ];
    }
}
