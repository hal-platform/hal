<?php
/**
 * @copyright ©2015 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Application;

use Exception;
use Psr\Log\LoggerInterface;

class ExceptionLogger
{
    /**
     * @type LoggerInterface
     */
    private $logger;

    /**
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
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
        $extext = <<<MSG
%s

Class: %s
File: %s

MSG;

        $file = sprintf('%s : %s', $ex->getFile(), $ex->getLine());
        $msg = sprintf($extext, $ex->getMessage(), get_class($ex), $file);

        $context = [
            'exceptionMessage' => $ex->getMessage(),
            'exceptionClass' => get_class($ex),
            'exceptionFile' => $file
        ];

        if ($prev = $ex->getPrevious()) {

            $file = sprintf('%s : %s', $prev->getFile(), $prev->getLine());
            $msg .= "\n\nPrevious Exception:\n\n" . sprintf($extext, $prev->getMessage(), get_class($prev), $file);

            $context += [
                'previousExceptionMessage' => $prev->getMessage(),
                'previousExceptionClass' => get_class($prev),
                'previousExceptionFile' => $file
            ];

            if ($prev = $prev->getPrevious()) {
                $file = sprintf('%s : %s', $prev->getFile(), $prev->getLine());
                $msg .= "\n\nPrevious Exception:\n\n" . sprintf($extext, $prev->getMessage(), get_class($prev), $file);
            }
        }

        $context['exceptionData'] = $msg;

        $logging = [$this->logger, $level];
        if (is_callable($logging)) {
            call_user_func($logging, $title, $context);
        }
    }
}
