<?php
/*
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2022 Improntus (http://www.improntus.com/)
 * @package Improntus_Rebill
 */

namespace Improntus\Rebill\Model\Api;

use Improntus\Rebill\Api\WebhookInterface;
use Improntus\Rebill\Model\Webhook as WebhookModel;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Webapi\Rest\Request;

class Webhook implements WebhookInterface
{
    /**
     * @var WebhookModel
     */
    private $webhook;

    /**
     * @var Request
     */
    private $request;

    /**
     * @param WebhookModel $webhook
     * @param Request $request
     */
    public function __construct(
        WebhookModel $webhook,
        Request $request
    ) {
        $this->webhook = $webhook;
        $this->request = $request;
    }

    /**
     * @param string $type
     * @return mixed|void
     * @throws CouldNotSaveException
     */
    public function execute(string $type)
    {
        $this->webhook->queueOrExecute($type, $this->request->getBodyParams());
    }
}
