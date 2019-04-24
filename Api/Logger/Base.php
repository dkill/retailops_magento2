<?php

namespace RetailOps\Api\Logger;

/**
 * Base logger class.
 *
 */
class Base extends \Magento\Framework\Logger\Handler\Base
{
    /**
     * Logging level
     * @var int
     */
    protected $loggerType = \Monolog\Logger::INFO;

    /**
     * File name
     * @var string
     */
    protected $fileName = '/var/log/retailops.log';
}
