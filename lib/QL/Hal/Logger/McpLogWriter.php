<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Logger;

use Psr\Log\LoggerInterface;
use Slim\Log;

/**
 * This is an adapter class to be able to send Slim's not-psr3-but-kind-of-close messages to a PSR-3 compliant logger.
 *
 * @internal
 */
class McpLogWriter
{
    /**
     * @var array
     */
    private $levels = [
        Log::EMERGENCY => 'EMERGENCY',
        Log::ALERT     => 'ALERT',
        Log::CRITICAL  => 'CRITICAL',
        Log::ERROR     => 'ERROR',
        Log::WARN      => 'WARNING',
        Log::NOTICE    => 'NOTICE',
        Log::INFO      => 'INFO',
        Log::DEBUG     => 'DEBUG'
    ];

    /**
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param string $message
     * @param int $level
     * @return boolean
     */
    public function write($message, $level = null)
    {
        // skip unknown messages
        if (!isset($this->levels[$level])) {
            return false;
        }

        $type = strtolower($this->levels[$level]);
        $this->logger->$type($message);
        return true;
    }
}
