<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Middleware\ACL;

use Hal\Core\Entity\User;
use Hal\UI\Controllers\SessionTrait;
use Hal\UI\Controllers\TemplatedControllerTrait;
use Hal\UI\Security\UserAuthorizations;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Panthor\MiddlewareInterface;
use QL\Panthor\TemplateInterface;

abstract class AbstractPermissionMiddleware implements MiddlewareInterface
{
    use TemplatedControllerTrait;
    use SessionTrait;

    /**
     * @var SignedInMiddleware
     */
    private $signedInMiddleware;

    /**
     * @var TemplateInterface
     */
    private $template;

    /**
     * @param SignedInMiddleware $signedInMiddleware
     * @param TemplateInterface $template
     */
    public function __construct(SignedInMiddleware $signedInMiddleware, TemplateInterface $template)
    {
        $this->signedInMiddleware = $signedInMiddleware;
        $this->template = $template;
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        $requreAuthorization = function (ServerRequestInterface $request, ResponseInterface $response) use ($next) {
            return $this->deferredMiddleware($request, $response, $next);
        };

        return ($this->signedInMiddleware)($request, $response, $requreAuthorization);
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param callable $next
     *
     * @return ResponseInterface
     */
    public function deferredMiddleware(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        $user = $this->getUser($request);
        $authorizations = $this->getAuthorizations($request);

        if ($user && $authorizations && $this->isAllowed($request, $user, $authorizations)) {
            return $next($request, $response);
        }

        // Denied
        return $this
            ->withTemplate($request, $response, $this->template)
            ->withStatus(403);
    }

    /**
     * @param ServerRequestInterface $request
     * @param User $user
     * @param UserAuthorizations $authorizations
     *
     * @return bool
     */
    abstract protected function isAllowed(ServerRequestInterface $request, User $user, UserAuthorizations $authorizations): bool;
}
