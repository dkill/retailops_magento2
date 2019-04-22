<?php

namespace RetailOps\Api\Config;

/**
 * Inventory type configuration class.
 *
 */
class InventoryType implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @var array
     */
    protected $statuses = [
        'internal' => 'internal',
        'empty' => 'empty',
        'external' => 'external'
    ];

    /**
     * @return array
     */
    public function toOptionArray()
    {
        $options = [];
        foreach ($this->statuses as $code => $label) {
            $options[] = ['value' => $code, 'label' => $label];
        }
        return $options;
    }
}
