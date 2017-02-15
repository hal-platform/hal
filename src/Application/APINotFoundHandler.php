<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Application;

use QL\Panthor\ErrorHandling\ExceptionHandlerInterface;
use QL\Panthor\ErrorHandling\ExceptionRendererInterface;
use QL\Panthor\Exception\NotFoundException;

class APINotFoundHandler implements ExceptionHandlerInterface
{
    /**
     * @var ExceptionRendererInterface
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
     * @inheritDoc
     */
    public function getHandledExceptions()
    {
        return [NotFoundException::class];
    }

    /**
     * @inheritDoc
     */
    public function handle($throwable)
    {
        if (!$throwable instanceof NotFoundException) return false;

        if (!isset($_SERVER['REQUEST_URI']) || substr($_SERVER['REQUEST_URI'], 0, 5) !== '/api/') {
            return false;
        }

        $status = 404;
        $context = [
            'message' => 'Resource Not Found',
            'status' => $status,
            'severity' => 'NotFound',
            'exception' => $throwable
        ];

        $this->renderer->render($status, $context);

        return true;
    }
}
