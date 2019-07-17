<?php

namespace Gudtech\RetailOps\Service;

use \Magento\Framework\Serialize\SerializerInterface;
use \Magento\Framework\Exception\SerializationException;

/**
 * Number page token class.
 *
 */
class NumberPageToken
{
    const SALT = 'ENJW8mS2KaJoNB5E5CoSAAu0xARgsR1bdzFWpEn+poYw45q+73az5kYi';

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * NumberPageToken constructor.
     *
     * @param SerializerInterface $serializer
     */
    public function __construct(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }

    public function encode($param)
    {
        return $this->generateCode($param);
    }

    public function decode($code)
    {
        return $this->getParamByCode($code);
    }

    protected function generateCode($param)
    {
        $array['page'] = $param;
        $array['key'] = self::SALT;
        $string = $this->serializer->serialize($array);
        return base64_encode($string);
    }

    protected function getParamByCode($code)
    {
        $string = base64_decode($code);
        $array = $this->serializer->unserialize($string);
        if (!is_array($array)) {
            throw new SerializationException(__('wrong pageNumberToken'));
        }
        if (isset($array['key']) && $array['key'] === self::SALT) {
            if (isset($array['page']) && is_numeric($array['page'])) {
                return (int)$array['page'];
            }
            throw new SerializationException(__('wrong pageNumberToken'));
        }
        throw new SerializationException(__('wrong pageNumberToken'));
    }
}
