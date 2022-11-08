<?php

namespace Improntus\Rebill\Gateway\Command;

use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Payment\Gateway\Command\ResultInterface;
use Magento\Payment\Gateway\CommandInterface;
use Magento\Sales\Model\Order\Payment;
use Magento\Sales\Model\OrderRepository;

class Initialize implements CommandInterface
{
    /**
     * @var OrderRepository
     */
    private $orderRepository;

    /**
     * @param OrderRepository $orderRepository
     */
    public function __construct(
        OrderRepository $orderRepository
    ) {
        $this->orderRepository = $orderRepository;
    }

    /**
     * @param array $commandSubject
     * @return ResultInterface|void|null|
     */
    public function execute(array $commandSubject)
    {
        try {
            /** @var Payment $infoInstance */
            $infoInstance = $commandSubject['payment'];
            $order = $infoInstance->getOrder();
            if ($order) {
                $order->setCanSendNewEmailFlag(false);
                $this->orderRepository->save($order);
            }
        } catch (\Exception $exception) {}
    }
}
