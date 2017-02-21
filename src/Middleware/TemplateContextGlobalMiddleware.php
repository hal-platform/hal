<?php
/**
 * @copyright (c) 2017 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Panthor\Twig\Context;
use QL\Panthor\MiddlewareInterface;

class TemplateContextGlobalMiddleware implements MiddlewareInterface
{
    const CONTEXT_ATTRIBUTE = 'template_context';

    /**
     * @var Context
     */
    private $context;

    /**
     * @param Context $context
     */
    public function __construct(Context $context)
    {
        $this->context = $context;
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        return $next($request->withAttribute(self::CONTEXT_ATTRIBUTE, $this->context), $response);
    }
}
