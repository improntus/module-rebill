<?php

namespace Improntus\Rebill\Cron;

use Exception;
use Improntus\Rebill\Helper\Config;
use Improntus\Rebill\Model\Entity\Queue\Repository;
use Improntus\Rebill\Model\Webhook;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;

class Queue
{
    /**
     * @var Webhook
     */
    private $webhook;

    /**
     * @var Repository
     */
    private $queueRepository;

    /**
     * @var Config
     */
    private $configHelper;

    /**
     * @param Webhook $webhook
     * @param Config $configHelper
     * @param Repository $queueRepository
     */
    public function __construct(
        Webhook    $webhook,
        Config     $configHelper,
        Repository $queueRepository
    ) {
        $this->configHelper = $configHelper;
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
            'status' => 'pending',
        ]);
        foreach ($queues->getItems() as $queue) {
            if ($this->queueRepository->validateStatus($queue->getId(), 'pending')) {
                $queue->setStatus('processing');
                $this->queueRepository->save($queue);
                try {
                    $this->webhook->execute($queue->getType(), $queue->getParameters());
                    $queue->setStatus('success');
                } catch (Exception $exception) {
                    $this->configHelper->logError($exception->getMessage());
                    $queue->setStatus('failed');
                }
                $this->queueRepository->save($queue);
            }
        }
    }
}
