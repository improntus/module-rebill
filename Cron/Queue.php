<?php

namespace Improntus\Rebill\Cron;

use Improntus\Rebill\Model\Entity\Queue\Repository;
use Improntus\Rebill\Model\Webhook;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;

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
     * @throws CouldNotDeleteException
     * @throws CouldNotSaveException
     */
    public function execute()
    {
        $queues = $this->queueRepository->getEzList([
            'status' => 'pending'
        ]);
        foreach ($queues->getItems() as $queue) {
            if ($this->queueRepository->validateStatus($queue->getId(), 'pending')) {
                $queue->setStatus('processing');
                $this->queueRepository->save($queue);
                $this->webhook->execute($queue->getType(), $queue->getParameters());
                $this->queueRepository->delete($queue);
            }
        }
    }
}
