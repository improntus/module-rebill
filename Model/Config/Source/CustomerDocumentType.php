<?php

namespace Improntus\Rebill\Model\Config\Source;

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
     * @return array
     */
    public function toOptionArray()
    {
        $documents = $this->data->getIdentificationsByGateway();
        $options = [];
        if ($documents && !isset($documents['statusCode'])) {
            foreach ($documents as $item) {
                $options[] = ['value' => $item['value'], 'label' => $item['name']];
            }
        }
        if (!$options) {
            $options[] = ['value' => 'DNI', 'label' => 'DNI'];
        }
        return $options;
    }
}
