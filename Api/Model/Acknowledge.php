<?php

namespace RetailOps\Api\Model;

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

    public function __construct(\RetailOps\Api\Model\Api\Acknowledge $acknowledge)
    {
        $this->acknowledge = $acknowledge;
    }
}
