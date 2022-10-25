<?php

namespace Improntus\Rebill\Gateway\Command;

use Magento\Payment\Gateway\CommandInterface;
use Magento\Payment\Gateway\Command\ResultInterface;

class Refund implements CommandInterface
{
    /**
     * @param array $commandSubject
     * @return ResultInterface|void|null
     */
    public function execute(array $commandSubject)
    {
        /**
         * @todo execute refund of the payment
         */
    }
}
