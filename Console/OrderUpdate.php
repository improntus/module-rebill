<?php

namespace Improntus\Rebill\Console;

use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class OrderUpdate extends Command
{
    protected $orderUpdate;

    /**
     * @param string|null $name
     */
    public function __construct(
        \Improntus\Rebill\Cron\OrderUpdate $orderUpdate,
        string $name = null
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
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->orderUpdate->execute();
    }
}
