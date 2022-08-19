<?php

namespace Improntus\Rebill\Model\Sales;

use Magento\Sales\Model\Order;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\QuoteRepository;
use Magento\Quote\Model\QuoteManagement;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Sales\Model\Reorder\Reorder as MagentoReorder;

class Reorder
{
    /**
     * @var MagentoReorder
     */
    protected $reorder;

    /**
     * @var QuoteRepository
     */
    protected $quoteRepository;

    /**
     * @var QuoteManagement
     */
    protected $cartManagement;

    public function __construct(
        MagentoReorder          $reorder,
        QuoteRepository         $quoteRepository,
        CartManagementInterface $cartManagement
    ) {
        $this->reorder = $reorder;
        $this->quoteRepository = $quoteRepository;
        $this->cartManagement = $cartManagement;
    }

    /**
     * @param Order $order
     * @return OrderInterface|null
     * @throws CouldNotSaveException
     * @throws InputException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function execute(Order $order)
    {
        $oldQuote = $this->quoteRepository->get($order->getQuoteId());
        $result = $this->reorder->execute($order->getIncrementId(), $order->getStoreId());
        /** @var Quote $cart */
        $cart = $result->getCart();
        $cart->setBillingAddress($oldQuote->getBillingAddress());
        $shippingAddress = $oldQuote->getShippingAddress()
            ->setShippingMethod($oldQuote->getShippingAddress()->getShippingMethod())
            ->setCollectShippingRates(true)
            ->collectShippingRates();
        $cart->setShippingAddress($shippingAddress);
        $cart->setPayment($oldQuote->getPayment());
        $cart->collectTotals();
        $this->quoteRepository->save($cart);
        return $this->cartManagement->submit($cart);
    }
}
