<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Middleware;

use Hal\UI\Controllers\TemplatedControllerTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Panthor\MiddlewareInterface;
use QL\Panthor\TemplateInterface;

class RemovalConfirmationMiddleware implements MiddlewareInterface
{
    use TemplatedControllerTrait;

    private const DELETION_METHODS = ['DELETE', 'POST'];

    /**
     * @var TemplateInterface
     */
    private $template;

    /**
     * @param TemplateInterface $template
     */
    public function __construct(TemplateInterface $template)
    {
        $this->template = $template;
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        if (!in_array($request->getMethod(), self::DELETION_METHODS, true)) {
            return $this->withTemplate($request, $response, $this->template);
        }

        return $next($request, $response);
    }
}
