<?php

namespace Improntus\Rebill\Cron;

use Exception;
use Improntus\Rebill\Api\Queue\SearchResultInterface;
use Improntus\Rebill\Helper\Config;
use Improntus\Rebill\Model\Entity\Queue\Repository;
use Improntus\Rebill\Model\Webhook;
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
     * @throws CouldNotSaveException
     */
    public function execute()
    {
        $queues = $this->queueRepository->getEzList([
            'status' => 'pending',
        ]);
        $this->processQueueList($queues);
        $retryingTime = $this->configHelper->getReorderRetryDays() ?: '7';
        $failedDate = date('Y-m-d', strtotime("-$retryingTime days"));
        $failedQueues = $this->queueRepository->getEzList([
            'status' => 'failed',
            'updated_at' => ['lteq' => $failedDate]
        ]);
        $this->processQueueList($failedQueues, 'failed', $failedDate);
    }

    /**
     * @param SearchResultInterface $queues
     * @param string $status
     * @param string|null $failedDate
     * @return void
     * @throws CouldNotSaveException
     */
    private function processQueueList(SearchResultInterface $queues, string $status = 'pending', ?string $failedDate = null)
    {
        foreach ($queues->getItems() as $queue) {
            if ($this->queueRepository->validateStatus($queue->getId(), $status, $failedDate)) {
                $queue->setStatus('processing');
                $this->queueRepository->save($queue);
                try {
                    $this->webhook->execute($queue->getType(), $queue->getParameters(), $queue->getId());
                    $queue->setStatus('success');
                } catch (Exception $exception) {
                    $this->configHelper->logError($exception->getMessage());
                    $queue->setStatus('failed');
                    $queue->setError($exception->getMessage());
                }
                $this->queueRepository->save($queue);
            }
        }
    }
}
