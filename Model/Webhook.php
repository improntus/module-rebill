<?php

namespace Improntus\Rebill\Model;

use Improntus\Rebill\Helper\Config;
use Improntus\Rebill\Model\Entity\Queue\Repository as QueueRepository;
use Improntus\Rebill\Model\Webhook\WebhookAbstract;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\CouldNotSaveException;

class Webhook
{
    /**
     * @var QueueRepository
     */
    protected $queueRepository;

    /**
     * @var Config
     */
    protected $configHelper;

    /**
     * @var array
     */
    protected $webhooks = [];

    /**
     * @param QueueRepository $queueRepository
     * @param Config $configHelper
     * @param array $webhooks
     */
    public function __construct(
        QueueRepository $queueRepository,
        Config          $configHelper,
        array           $webhooks = []
    ) {
        $this->webhooks = $webhooks;
        $this->queueRepository = $queueRepository;
        $this->configHelper = $configHelper;
    }

    /**
     * @param string $type
     * @param array $parameters
     * @return void
     * @throws CouldNotSaveException
     */
    public function queueOrExecute(string $type, array $parameters)
    {
        if ($this->configHelper->isEnqueueWebhooksEnabled()) {
            $queue = $this->queueRepository->create();
            $queue->setType($type);
            $queue->setStatus('pending');
            $queue->setParameters($parameters);
            $this->queueRepository->save($queue);
        } else {
            $this->execute($type, $parameters);
        }
    }

    /**
     * @param string $type
     * @param array $parameters
     * @param int|null $queueId
     * @return void
     */
    public function execute(string $type, array $parameters, int $queueId = null)
    {
        if (!isset($this->webhooks[$type]) || !class_exists($this->webhooks[$type])) {
            return;
        }
        /** @var WebhookAbstract $webhook */
        $webhook = ObjectManager::getInstance()->create(
            $this->webhooks[$type],
            ['parameters' => $parameters, 'queueId' => $queueId]
        );
        $webhook->execute();
    }
}
