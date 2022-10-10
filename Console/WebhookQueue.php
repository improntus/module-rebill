<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2022 Improntus (http://www.improntus.com/)
 * @package Improntus_Rebill
 */

namespace Improntus\Rebill\Console;

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
     * @param CronAction $cronAction
     * @param string|null $name
     */
    public function __construct(
        CronAction $cronAction,
        string     $name = null
    ) {
        $this->cronAction = $cronAction;
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
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->cronAction->execute();
    }
}
