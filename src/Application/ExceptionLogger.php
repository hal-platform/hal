<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Hal\Application;

use Exception;
use Psr\Log\LoggerInterface;
use QL\Panthor\ErrorHandling\StacktraceFormatterTrait;

class ExceptionLogger
{
    use StacktraceFormatterTrait;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;

        $this->setStacktraceLogging(true);
    }

    /**
     * @param string $title
     * @param Exception $ex
     * @param string $level
     *
     * @return void
     */
    public function logException($title, Exception $ex, $level = 'warning')
    {
        $exceptions = $this->unpackExceptions($ex);

        $context = [
            'exceptionClass' => get_class($ex),
            'exceptionData' => $this->formatStacktraceForExceptions($exceptions)
        ];

        $logging = [$this->logger, $level];
        if (is_callable($logging)) {
            call_user_func($logging, $title, $context);
        }
    }

    /**
     * @param Exception $exception
     *
     * @return Exception[]
     */
    private function unpackExceptions(Exception $exception)
    {
        $exceptions = [$exception];

        $e = $exception;
        while ($e = $e->getPrevious()) {
            $exceptions[] = $e;
        }

        return $execeptions;
    }
}
