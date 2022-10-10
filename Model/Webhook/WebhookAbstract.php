<?php

namespace Improntus\Rebill\Model\Webhook;

abstract class WebhookAbstract
{
    /**
     * @var array
     */
    protected $parameters;

    /**
     * @param array $parameters
     */
    public function __construct(
        array $parameters = []
    ) {
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
     * @param string $parameter
     * @return mixed|null
     */
    protected function getParameter(string $parameter)
    {
        return $this->parameters[$parameter] ?? null;
    }
}
