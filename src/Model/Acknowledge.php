<?php

namespace Gudtech\RetailOps\Model;

/**
 * Acknowlegde class.
 *
 */
class Acknowledge
{
    /**
     * @var Api\Acknowledge
     */
    protected $acknowledge;

    /**
     * @param array $postData
     * @return array
     */
    public function setOrderRefs($postData)
    {
        if ($postData['orders']) {
            $events = $this->acknowledge->setOrderNumbers($postData['orders']);
            return $events;
        }
        return [];
    }

    public function __construct(\Gudtech\RetailOps\Model\Api\Acknowledge $acknowledge)
    {
        $this->acknowledge = $acknowledge;
    }
}
