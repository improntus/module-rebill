<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2022 Improntus (http://www.improntus.com/)
 * @package Improntus_Rebill
 */

namespace Improntus\Rebill\Model\Config\Source;

use Exception;
use Improntus\Rebill\Model\Rebill\Data;
use Magento\Framework\Option\ArrayInterface;

class CustomerDocumentType implements ArrayInterface
{
    /**
     * @var Data
     */
    protected $data;

    /**
     * @param Data $data
     */
    public function __construct(
        Data $data
    ) {
        $this->data = $data;
    }

    /**
     * @param $gateway
     * @return array
     */
    public function toOptionArray($gateway = null)
    {
        $options = [];
        try {
            $documents = $this->data->getIdentificationsByGateway($gateway);
            if ($documents && !isset($documents['statusCode'])) {
                foreach ($documents as $item) {
                    $options[] = ['value' => $item['value'], 'label' => $item['name']];
                }
            }
        } catch (Exception $exception) {

        }
        return $options;
    }
}
