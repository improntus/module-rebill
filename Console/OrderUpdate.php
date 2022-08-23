<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2022 Improntus (http://www.improntus.com/)
 * @package Improntus_Rebill
 */

namespace Improntus\Rebill\Console;

use Symfony\Component\Console\Command\Command;
use Magento\Framework\Exception\InputException;
use Symfony\Component\Console\Input\InputInterface;
use Magento\Framework\Exception\LocalizedException;
use Symfony\Component\Console\Output\OutputInterface;
use Improntus\Rebill\Cron\OrderUpdate as CronAction;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\CouldNotSaveException;

/**
 * @description Adds the possibility of update the subscription prices manually
 */
class OrderUpdate extends Command
{
    /**
     * @var CronAction
     */
    protected $orderUpdate;

    /**
     * @param string|null $name
     */
    public function __construct(
        CronAction $orderUpdate,
        string     $name = null
    ) {
        $this->orderUpdate = $orderUpdate;
        parent::__construct($name);
    }

    /**
     * @return void
     */
    protected function configure()
    {
        $this->setName("rebill:order:update");
        $this->setDescription("Rebill Order Update");
        parent::configure();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void
     * @throws CouldNotSaveException
     * @throws InputException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->orderUpdate->execute();
    }
}
