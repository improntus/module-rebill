<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2022 Improntus (http://www.improntus.com/)
 * @package Improntus_Rebill
 */

namespace Improntus\Rebill\Console;

use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Improntus\Rebill\Cron\Queue as CronAction;

/**
 * @description Adds the possibility of update the subscription prices manually
 */
class WebhookQueue extends Command
{
    /**
     * @var CronAction
     */
    protected $cronAction;

    /**
     * @var State
     */
    protected $state;

    /**
     * @param CronAction $cronAction
     * @param State $state
     * @param string|null $name
     * @throws LocalizedException
     */
    public function __construct(
        CronAction $cronAction,
        State      $state,
        string     $name = null
    ) {
        $this->state = $state;
        $this->cronAction = $cronAction;
        if (!$this->state->getAreaCode()) {
            $this->state->setAreaCode(Area::AREA_GLOBAL);
        }
        parent::__construct($name);
    }

    /**
     * @return void
     */
    protected function configure()
    {
        $this->setName("rebill:webhook:queue");
        $this->setDescription("Rebill Webhook Queue");
        parent::configure();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void
     * @throws CouldNotDeleteException
     * @throws CouldNotSaveException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->cronAction->execute();
    }
}
