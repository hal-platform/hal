<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Hal\UI\Auth;
use Hal\UI\Middleware\UserSessionGlobalMiddleware;
use Hal\UI\Session;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Hal\Core\Entity\User;
use QL\Hal\Core\Entity\UserSettings;
use QL\Panthor\MiddlewareInterface;
use QL\Panthor\Utility\URI;

class SignInHandler implements MiddlewareInterface
{
    use RedirectableControllerTrait;
    use SessionTrait;
    use TemplatedControllerTrait;

    private const ERR_INVALID = 'A username and password must be entered.';
    private const ERR_AUTH_FAILURE = 'Authentication failed.';
    private const ERR_DISABLED = 'Account disabled.';

    /**
     * @var Auth
     */
    private $auth;

    /**
     * @var UserRepository
     */
    private $userRepo;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var URI
     */
    private $uri;

    /**
     * @var callable
     */
    private $random;

    /**
     * @param Auth $auth
     * @param EntityManagerInterface $em
     * @param URI $uri
     * @param callable $random
     */
    public function __construct(
        Auth $auth,
        EntityManagerInterface $em,
        URI $uri,
        callable $random
    ) {
        $this->auth = $auth;
        $this->userRepo = $em->getRepository(User::class);
        $this->em = $em;
        $this->uri = $uri;
        $this->random = $random;
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        if ($request->getMethod() !== 'POST') {
            return $next($request, $response);
        }

        $username = $request->getParsedBody()['username'] ?? null;
        $password = $request->getParsedBody()['password'] ?? null;
        $redirect = $request->getQueryParams()['redirect'] ?? null;

        // auth empty
        if (!$username || !$password) {
            return $next($this->withError($request, self::ERR_INVALID), $response);
        }

        // auth failed
        if (!$account = $this->auth->authenticate($username, $password)) {
            return $next($this->withError($request, self::ERR_AUTH_FAILURE), $response);
        }

        $user = $this->userRepo->findOneBy(['handle' => $account['username']]);

        // account disabled manually
        if ($user && !$user->isActive()) {
            return $next($this->withError($request, self::ERR_DISABLED), $response);
        }

        $isFirstLogin = false;
        if (!$user) {
            $isFirstLogin = true;
            $user = (new User)
                ->withIsActive(true);
        }

        $this->updateUserDetails($user, $account, $isFirstLogin);

        $session = $this->getSession($request);

        $session->clear();
        $session->set(UserSessionGlobalMiddleware::SESSION_ATTRIBUTE, $user->id());
        $session->set('is_first_login', $isFirstLogin);

        if ($redirect && strpos($redirect, '/') === 0) {
            return $this->withRedirectURL($response, $request->getUri(), $redirect);
        } else {
            return $this->withRedirectRoute($response, $this->uri, 'dashboard');
        }
    }

    /**
     * @param ServerRequestInterface $request
     * @param string $error
     *
     * @return ServerRequestInterface
     */
    private function withError(ServerRequestInterface $request, string $error)
    {
        $context = [
            'errors' => [$error]
        ];

        return $this->withContext($request, $context);
    }

    /**
     * @param User $user
     * @param array $account
     * @param bool $isFirstLogin
     *
     * @return null
     */
    private function updateUserDetails(User $user, array $account, $isFirstLogin)
    {
        if ($isFirstLogin) {
            // generate an id between 100,000,000 - 200,000,000.
            // @todo change id to guid
            $id = \random_int(100000000, 200000000);

            $user
                ->withId($id)
                ->withHandle($account['username']);
        }

        // Always ensure email and name is in sync
        $user
            ->withEmail($account['email'] )
            ->withName($account['name'] );

        // Add user settings if not set.
        if (!$user->settings()) {
            $settings = (new UserSettings(call_user_func($this->random)))
                ->withUser($user);

            $this->em->persist($settings);
        }

        $this->em->persist($user);
        $this->em->flush();
    }
}