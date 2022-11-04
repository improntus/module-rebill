<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2022 Improntus (http://www.improntus.com/)
 * @package Improntus_Rebill
 */

namespace Improntus\Rebill\Controller\Adminhtml\Queue;

use Exception;
use Improntus\Rebill\Model\Entity\Queue\Repository;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\ResponseInterface;

class Retry extends Action
{
    /**
     * @var Repository
     */
    private $queueRepository;

    /**
     * @param Context $context
     * @param Repository $queueRepository
     */
    public function __construct(
        Context    $context,
        Repository $queueRepository
    ) {
        $this->queueRepository = $queueRepository;
        parent::__construct($context);
    }

    /**
     * @return ResponseInterface
     * @throws Exception
     */
    public function execute()
    {
        if ($id = $this->getRequest()->getParam('id')) {
            $queue = $this->queueRepository->getById($id);
            if ($queue->getId() && $queue->getStatus() == 'failed') {
                $queue->setStatus('pending');
                $this->queueRepository->save($queue);
                $this->getMessageManager()->addSuccessMessage(__('Failed queued process will be tried again.'));
            } else {
                $this->getMessageManager()->addSuccessMessage(
                    __('Queued process doesn\'t exists or it isn\'t failed.')
                );
            }
        }
        return $this->_redirect('rebill/queue/index');
    }
}
