<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Middleware;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Hal\UI\Controllers\RedirectableControllerTrait;
use Hal\UI\Controllers\SessionTrait;
use Hal\UI\Controllers\TemplatedControllerTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Hal\Core\Entity\User;
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

    private const ROUTE_SIGNOUT = 'signout';

    /**
     * @var EntityRepository
     */
    private $userRepo;

    /**
     * @var URI
     */
    private $uri;

    /**
     * @param EntityManagerInterface $em
     * @param URI $uri
     */
    public function __construct(EntityManagerInterface $em, URI $uri)
    {
        $this->userRepo = $em->getRepository(User::class);
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

        // sign out user if not found
        if (!$user = $this->userRepo->find($userID)) {
            // @todo CHANGE TO POST!!!!
            return $this->withRedirectRoute($response, $this->uri, self::ROUTE_SIGNOUT);
        }

        // Add user to the server attrs for controllers/middleware
        // Add user to template context for templates
        $request = $this
            ->withContext($request, [self::USER_ATTRIBUTE => $user])
            ->withAttribute(self::USER_ATTRIBUTE, $user);

        // Save user to request attributes
        return $next($request, $response);
    }
}