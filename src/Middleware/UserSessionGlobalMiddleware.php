<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Middleware;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Hal\Core\Entity\User;
use Hal\Core\Entity\UserPermission;
use Hal\UI\Controllers\RedirectableControllerTrait;
use Hal\UI\Controllers\SessionTrait;
use Hal\UI\Controllers\TemplatedControllerTrait;
use Hal\UI\Security\AuthorizationService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\MCP\Logger\MessageFactoryInterface;
use QL\MCP\Logger\MessageInterface;
use QL\Panthor\MiddlewareInterface;
use QL\Panthor\Utility\URI;

/**
 * If there is a user session, load the User.
 *
 * The user is loaded from the database and populates into:
 * - Request (attribute: current_user)
 * - Template Context (variable: current_user)
 */
class UserSessionGlobalMiddleware implements MiddlewareInterface
{
    use RedirectableControllerTrait;
    use SessionTrait;
    use TemplatedControllerTrait;

    public const SESSION_ATTRIBUTE = 'user_id';
    public const USER_ATTRIBUTE = 'current_user';
    public const AUTHORIZATIONS_ATTRIBUTE = 'current_authorizations';

    private const ROUTE_SIGNOUT = 'signout';

    /**
     * @var EntityRepository
     */
    private $userRepo;
    private $permissionRepo;

    /**
     * @var AuthorizationService
     */
    private $authorizationService;

    /**
     * @var URI
     */
    private $uri;

    /**
     * @var MessageFactoryInterface|null
     */
    private $factory;

    /**
     * @param EntityManagerInterface $em
     * @param AuthorizationService $authorizationService
     * @param URI $uri
     */
    public function __construct(
        EntityManagerInterface $em,
        AuthorizationService $authorizationService,
        URI $uri
    ) {
        $this->userRepo = $em->getRepository(User::class);
        $this->permissionRepo = $em->getRepository(UserPermission::class);

        $this->authorizationService = $authorizationService;
        $this->uri = $uri;
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        $session = $this->getSession($request);

        // If no user-id, continue on seamlessly.
        if (!$userID = $session->get(self::SESSION_ATTRIBUTE)) {
            return $next($request, $response);
        }

        $user = $this->userRepo->find($userID);

        // sign out user if not found, or is disabled
        if (!$user || $user->isDisabled()) {
            //stops redirect
            $this->getSession($request)->clear();
            // @todo CHANGE TO POST!!!!
            return $this->withRedirectRoute($response, $this->uri, self::ROUTE_SIGNOUT);
        }

        $this->attachUserToLogger($user);

        $request = $this->appendUserToRequest($request, $user);
        $request = $this->appendAuthorizationsToRequest($request, $user);

        // Save user to request attributes
        return $next($request, $response);
    }

    /**
     * Add user to the server attrs for controllers/middleware
     * Add user to template context for templates
     *
     * @param ServerRequestInterface $request
     * @param User $user
     *
     * @return ServerRequestInterface
     */
    private function appendUserToRequest(ServerRequestInterface $request, User $user): ServerRequestInterface
    {
        $request = $this
            ->withContext($request, [self::USER_ATTRIBUTE => $user])
            ->withAttribute(self::USER_ATTRIBUTE, $user);

        return $request;
    }

    /**
     * Add authorizations to the server attrs for controllers/middleware
     * Add authorizations to template context for templates
     *
     * @param ServerRequestInterface $request
     * @param User $user
     *
     * @return ServerRequestInterface
     */
    private function appendAuthorizationsToRequest(ServerRequestInterface $request, User $user): ServerRequestInterface
    {
        $authorizations = $this->authorizationService->getUserAuthorizations($user);

        $request = $this
            ->withContext($request, [self::AUTHORIZATIONS_ATTRIBUTE => $authorizations])
            ->withAttribute(self::AUTHORIZATIONS_ATTRIBUTE, $authorizations);

        return $request;
    }

    /**
     * @param User $user
     *
     * @return void
     */
    private function attachUserToLogger(User $user)
    {
        if (!$this->factory) {
            return;
        }

        $this->factory->setDefaultProperty(MessageInterface::USER_NAME, $user->username());
    }

    /**
     * @param MessageFactoryInterface $factory
     *
     * @return void
     */
    public function setLoggerMessageFactory(MessageFactoryInterface $factory)
    {
        $this->factory = $factory;
    }
}
