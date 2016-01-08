<?php
/**
 * @copyright ©2015 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Application;

use Exception;
use QL\Panthor\ErrorHandling\ExceptionHandlerInterface;
use QL\Panthor\ErrorHandling\ExceptionRendererInterface;
use QL\Panthor\Exception\NotFoundException;

/**
 * Handler for 404s
 */
class APINotFoundHandler implements ExceptionHandlerInterface
{
    /**
     * @type ExceptionRendererInterface
     */
    private $renderer;

    /**
     * @param ExceptionRendererInterface $renderer
     */
    public function __construct(ExceptionRendererInterface $renderer)
    {
        $this->renderer = $renderer;
    }

    /**
     * {@inheritdoc}
     */
    public function getHandledExceptions()
    {
        return [NotFoundException::CLASS];
    }

    /**
     * {@inheritdoc}
     */
    public function handle(Exception $exception)
    {
        if (!$exception instanceof NotFoundException) return false;

        if (!isset($_SERVER['REQUEST_URI']) || substr($_SERVER['REQUEST_URI'], 0, 5) !== '/api/') {
            return false;
        }

        $status = 404;
        $context = [
            'message' => 'Resource Not Found',
            'status' => $status,
            'severity' => 'NotFound',
            'exception' => $exception
        ];

        $this->renderer->render($status, $context);

        return true;
    }
}
