<?php
/**
 * @copyright (c) 2017 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers;

use Hal\UI\Middleware\TemplateContextGlobalMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Panthor\HTTP\NewBodyTrait;
use QL\Panthor\TemplateInterface;
use QL\Panthor\Twig\Context;

trait TemplatedControllerTrait
{
    use NewBodyTrait;

    /**
     * Add additional template context to the request.
     *
     * Even if no context was previously added, Context WILL ALWAYS be available at "template_context"
     * after this method is run.
     *
     * @param ResponseInterface $response
     * @param array $context
     *
     * @return ServerRequestInterface
     */
    private function withContext(ServerRequestInterface $request, array $context = []): ServerRequestInterface
    {
        if (!$context) {
            return $request;
        }

        $savedContext = $request->getAttribute(TemplateContextGlobalMiddleware::CONTEXT_ATTRIBUTE) ?: new Context;

        $savedContext->addContext($context);

        return $request->withAttribute(TemplateContextGlobalMiddleware::CONTEXT_ATTRIBUTE, $savedContext);
    }

    /**
     * Render a template and add it to the response.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param TemplateInterface $template
     * @param array $context
     *
     * @return ResponseInterface
     */
    private function withTemplate(
        ServerRequestInterface $request,
        ResponseInterface $response,
        TemplateInterface $template,
        array $context = []
    ): ResponseInterface {
        if ($savedContext = $request->getAttribute(TemplateContextGlobalMiddleware::CONTEXT_ATTRIBUTE)) {
            $savedContext->addContext($context);
            $context = $savedContext->get();
        }

        $rendered = $template->render($context);

        return $this->withNewBody($response, $rendered);
    }
}
