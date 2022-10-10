<?php

namespace Improntus\Rebill\Model;

use Improntus\Rebill\Helper\Config;
use Improntus\Rebill\Model\Entity\Queue\Repository as QueueRepository;
use Improntus\Rebill\Model\Webhook\Confirmation;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\CouldNotSaveException;

class Webhook
{
    private const WEBHOOKS = [
        'confirmation' => Confirmation::class,
    ];

    /**
     * @var QueueRepository
     */
    protected $queueRepository;

    /**
     * @var Config
     */
    protected $configHelper;

    /**
     * @param QueueRepository $queueRepository
     * @param Config $configHelper
     */
    public function __construct(
        QueueRepository $queueRepository,
        Config          $configHelper
    ) {
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
     * @return void
     */
    public function execute(string $type, array $parameters)
    {
        ObjectManager::getInstance()->create(self::WEBHOOKS[$type], ['parameters' => $parameters]);
    }
}
