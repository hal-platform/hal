<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Security;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Hal\Core\Entity\User;
use Hal\UI\Controllers\SessionTrait;
use Hal\UI\Controllers\TemplatedControllerTrait;
use Psr\Http\Message\ServerRequestInterface;
use QL\MCP\Common\GUID;
use QL\MCP\Common\Time\Clock;
use QL\Panthor\Session\SessionInterface;

class UserSessionHandler
{
    use SessionTrait;
    use TemplatedControllerTrait;

    public const SESSION_ID_ATTRIBUTE = 'session_id';
    public const SESSION_CREATED_ATTRIBUTE = 'session_created';
    public const SESSION_USER_ATTRIBUTE = 'user_id';

    public const REQUEST_USER_ATTRIBUTE = 'current_user';
    public const REQUEST_AUTHORIZATIONS_ATTRIBUTE = 'current_authorizations';

    /**
     * @var EntityRepository
     */
    private $userRepo;

    /**
     * @var AuthorizationService
     */
    private $authorizationService;

    /**
     * @var Clock
     */
    private $clock;

    /**
     * @param EntityManagerInterface $em
     * @param AuthorizationService $authorizationService
     * @param Clock $clock
     */
    public function __construct(
        EntityManagerInterface $em,
        AuthorizationService $authorizationService,
        Clock $clock
    ) {
        $this->userRepo = $em->getRepository(User::class);

        $this->authorizationService = $authorizationService;
        $this->clock = $clock;
    }

    /**
     * Get the session. Guaranteed fresh with the data we need.
     *
     * @param ServerRequestInterface $request
     *
     * @return SessionInterface
     */
    public function getFreshSession(ServerRequestInterface $request): SessionInterface
    {
        $session = $this->getSession($request);

        $sessionID = $session->get(self::SESSION_ID_ATTRIBUTE);
        $sessionCreated = $session->get(self::SESSION_CREATED_ATTRIBUTE);

        if ($sessionCreated) {
            $sessionCreated = $this->clock->fromString($sessionCreated);
        }

        if (!$sessionID || !$sessionCreated) {
            $session = $this->buildNewSession($session, '');
        }

        return $session;
    }

    /**
     * @param ServerRequestInterface $request
     * @param string|null $userID
     *
     * @return SessionInterface
     */
    public function startNewSession(ServerRequestInterface $request, $userID)
    {
        $session = $this->getSession($request);
        return $this->buildNewSession($session, $userID);
    }

    /**
     * @param ServerRequestInterface $request
     * @param SessionInterface $session
     *
     * @return ServerRequestInterface|null
     */
    public function attachSessionUserToRequest(ServerRequestInterface $request, SessionInterface $session): ?ServerRequestInterface
    {
        $userID = $session->get(self::SESSION_USER_ATTRIBUTE);

        return $this->attachUserToRequest($request, $userID);
    }

    /**
     * @param ServerRequestInterface $request
     * @param string|null $userID
     *
     * @return ServerRequestInterface|null
     */
    public function attachUserToRequest(ServerRequestInterface $request, $userID): ?ServerRequestInterface
    {
        if (!$userID) {
            return $request;
        }

        $user = $this->userRepo->find($userID);

        if (!$user instanceof User || $user->isDisabled()) {
            return null;
        }

        // Save user to request attributes
        $request = $this->appendUserToRequest($request, $user);
        $request = $this->appendAuthorizationsToRequest($request, $user);

        return $request;
    }

    /**
     * @param SessionInterface $session
     * @param string|null $userID
     *
     * @return SessionInterface
     */
    private function buildNewSession(SessionInterface $session, $userID)
    {
        $sessionID = GUID::create()->format(GUID::STANDARD | GUID::HYPHENATED);
        $now = $this->clock
            ->read()
            ->format('Y-m-d\TH:i:s\Z', 'UTC');

        $session->clear();
        $session->set(self::SESSION_ID_ATTRIBUTE, $sessionID);
        $session->set(self::SESSION_CREATED_ATTRIBUTE, $now);

        if ($userID) {
            $session->set(self::SESSION_USER_ATTRIBUTE, $userID);
        }

        return $session;
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
    private function appendUserToRequest(ServerRequestInterface $request, User $user)
    {
        $request = $this
            ->withContext($request, [self::REQUEST_USER_ATTRIBUTE => $user])
            ->withAttribute(self::REQUEST_USER_ATTRIBUTE, $user);

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
    private function appendAuthorizationsToRequest(ServerRequestInterface $request, User $user)
    {
        $authorizations = $this->authorizationService->getUserAuthorizations($user);

        $request = $this
            ->withContext($request, [self::REQUEST_AUTHORIZATIONS_ATTRIBUTE => $authorizations])
            ->withAttribute(self::REQUEST_AUTHORIZATIONS_ATTRIBUTE, $authorizations);

        return $request;
    }
}
