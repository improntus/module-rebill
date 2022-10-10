<?php

namespace Improntus\Rebill\Cron;

use Improntus\Rebill\Model\Entity\Queue\Repository;
use Improntus\Rebill\Model\Webhook;

class Queue
{
    /**
     * @var Webhook
     */
    protected $webhook;

    /**
     * @var Repository
     */
    protected $queueRepository;

    /**
     * @param Webhook $webhook
     * @param Repository $queueRepository
     */
    public function __construct(
        Webhook    $webhook,
        Repository $queueRepository
    ) {
        $this->queueRepository = $queueRepository;
        $this->webhook = $webhook;
    }

    /**
     * @return void
     */
    public function execute()
    {
        $queues = $this->queueRepository->getEzList([
            'status' => 'pending'
        ]);
        foreach ($queues->getItems() as $queue) {
            if ($this->queueRepository->validateStatus($queue->getId(), 'pending')) {
                $this->webhook->execute($queue->getId(), $queue->getParameters());
            }
        }
    }
}
