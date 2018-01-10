<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Middleware;

use Hal\Core\Entity\User;
use Hal\UI\Controllers\RedirectableControllerTrait;
use Hal\UI\Controllers\SessionTrait;
use Hal\UI\Controllers\TemplatedControllerTrait;
use Hal\UI\Security\CSRFManager;
use Hal\UI\Security\UserSessionHandler;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\MCP\Logger\MessageFactoryInterface;
use QL\MCP\Logger\MessageInterface;
use QL\Panthor\MiddlewareInterface;
use QL\Panthor\Session\SessionInterface;
use QL\Panthor\Utility\URI;
use function random_bytes;

/**
 * - Ensure a session cookie is present and load session details in the request and template context if so.
 * - Add CSRF details to session/memory
 */
class UserSessionGlobalMiddleware implements MiddlewareInterface
{
    use RedirectableControllerTrait;
    use SessionTrait;
    use TemplatedControllerTrait;

    public const CSRF_ATTRIBUTE = 'csrf';

    /**
     * @var UserSessionHandler
     */
    private $userHandler;

    /**
     * @var CSRFManager
     */
    private $csrf;

    /**
     * @var URI
     */
    private $uri;

    /**
     * @var MessageFactoryInterface|null
     */
    private $factory;

    /**
     * @param UserSessionHandler $userHandler
     * @param CSRFManager $csrf
     * @param URI $uri
     */
    public function __construct(UserSessionHandler $userHandler, CSRFManager $csrf, URI $uri)
    {
        $this->userHandler = $userHandler;
        $this->csrf = $csrf;
        $this->uri = $uri;
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        $session = $this->userHandler->getFreshSession($request);

        $sessionID = $session->get(UserSessionHandler::SESSION_ID_ATTRIBUTE);

        if ($csrfs = $session->get(self::CSRF_ATTRIBUTE)) {
            $this->csrf->loadCSRFs($csrfs, $sessionID);
        }

        $request = $this->userHandler->attachSessionUserToRequest($request, $session);

        // sign out user if not found, or is disabled
        // Note this does not fail if NO user session is provided.
        // Only if a user ID session exists and is invalid
        if (!$request) {
            $session->clear();
            return $this->withRedirectRoute($response, $this->uri, 'signin');
        }

        // Attach user details to logger if user session is present.
        if ($user = $this->getUser($request)) {
            $this->attachUserToLogger($user);
        }

        $response = $next($request, $response);

        // Set the CSRFs so they can be rendered back out
        $session->set(self::CSRF_ATTRIBUTE, $this->csrf->getCSRFs());

        return $response;
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

        $this->factory->setDefaultProperty(MessageInterface::USER_NAME, $user->name());
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
