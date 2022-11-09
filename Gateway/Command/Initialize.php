<?php

namespace Improntus\Rebill\Gateway\Command;

use Exception;
use Magento\Payment\Gateway\Command\ResultInterface;
use Magento\Payment\Gateway\CommandInterface;
use Magento\Payment\Gateway\Data\OrderAdapterInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\OrderRepository;

class Initialize implements CommandInterface
{
    /**
     * @var OrderRepository
     */
    private $orderRepository;

    /**
     * @var OrderFactory
     */
    private $orderFactory;

    /**
     * @param OrderRepository $orderRepository
     * @param OrderFactory $orderFactory
     */
    public function __construct(
        OrderRepository $orderRepository,
        OrderFactory    $orderFactory
    ) {
        $this->orderRepository = $orderRepository;
        $this->orderFactory = $orderFactory;
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
            /** @var Order|OrderAdapterInterface $order */
            $order = $infoInstance->getOrder();
            if ($order && ($order->getId() || $order->getOrderIncrementId())) {
                if (!$order instanceof Order) {
                    if ($order->getId()) {
                        $order = $this->orderRepository->get($order->getId());
                    } else if ($incrementId = $order->getOrderIncrementId()) {
                        $order = $this->orderFactory->create();
                        $order->loadByIncrementId($incrementId);
                    }
                }
                if ($order instanceof Order) {
                    $order->setCanSendNewEmailFlag(false);
                    $this->orderRepository->save($order);
                }
            }
        } catch (Exception $exception) {
        }
    }
}
