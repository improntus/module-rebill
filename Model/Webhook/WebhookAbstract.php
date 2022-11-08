<?php

namespace Improntus\Rebill\Model\Webhook;

abstract class WebhookAbstract
{
    /**
     * @var array
     */
    protected $parameters;

    /**
     * @var int
     */
    protected $queueId;

    /**
     * @param array $parameters
     * @param int|null $queueId
     */
    public function __construct(
        array $parameters = [],
        ?int  $queueId = null
    ) {
        $this->queueId = $queueId;
        $this->parameters = $parameters;
    }

    /**
     * @return mixed
     */
    abstract public function execute();

    /**
     * @return array
     */
    protected function getParameters()
    {
        return $this->parameters;
    }

    /**
     * @param int $queueId
     * @return $this
     */
    protected function setQueueId(int $queueId)
    {
        $this->queueId = $queueId;
        return $this;
    }

    /**
     * @param string $parameter
     * @return mixed|null
     */
    protected function getParameter(string $parameter)
    {
        return $this->parameters[$parameter] ?? null;
    }
}
