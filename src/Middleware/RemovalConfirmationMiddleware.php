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
     * @var string
     */
    private $removeEntityType;

    /**
     * @param TemplateInterface $template
     * @param string $removeEntityType
     */
    public function __construct(TemplateInterface $template, $removeEntityType)
    {
        $this->template = $template;
        $this->removeEntityType = $removeEntityType;
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        if (in_array($request->getMethod(), self::DELETION_METHODS, true)) {
            return $next($request, $response);
        }

        $context = $this->buildDeletionContext($request);

        return $this->withTemplate($request, $response, $this->template, $context);
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return array
     */
    private function buildDeletionContext(ServerRequestInterface $request)
    {
        $context = [
            'entities' => [],
            'remove' => []
        ];

        // Cycle through all possible uri entities and add them to the template context.
        foreach (RequireEntityMiddleware::KNOWN_ENTITIES as $param => $attributeName) {
            if ($entity = $request->getAttribute($attributeName)) {
                $context['entities'][$param] = $entity;

                // If we found the "primary" entity to be deleted, add it separately
                if ($param === $this->removeEntityType) {
                    $context['remove'] = [
                        'param' => $this->removeEntityType,
                        'class' => $attributeName,
                        'entity' => $request->getAttribute($attributeName)
                    ];
                }
            }
        }

        return $context;
    }
}
