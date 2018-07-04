<?php
/**
 * @copyright (c) 2018 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Auth;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Hal\Core\Entity\System\UserIdentityProvider;
use Hal\Core\Entity\User;
use Hal\UI\Controllers\RedirectableControllerTrait;
use Hal\UI\Controllers\SessionTrait;
use Hal\UI\Security\Auth;
use Hal\UI\Security\UserSessionHandler;
use Hal\UI\Validator\ValidatorErrorTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Panthor\HTTP\CookieHandler;
use QL\Panthor\MiddlewareInterface;
use QL\Panthor\TemplateInterface;
use QL\Panthor\Utility\URI;

class SignInCallbackHandler implements MiddlewareInterface
{
    use SessionTrait;
    use RedirectableControllerTrait;
    use ValidatorErrorTrait;

    private const ERR_DISABLED = 'Account disabled.';

    private const IDP_COOKIE_NAME = 'idp';

    /**
     * @var EntityRepository
     */
    private $idpRepo;

    /**
     * @var Auth
     */
    private $auth;

    /**
     * @var UserSessionHandler
     */
    private $userHandler;

    /**
     * @var CookieHandler
     */
    private $cookies;

    /**
     * @var URI
     */
    private $uri;

    /**
     * @param EntityManagerInterface $em
     * @param Auth $auth
     * @param UserSessionHandler $userHandler
     * @param CookieHandler $cookies
     * @param URI $uri
     */
    public function __construct(
        EntityManagerInterface $em,
        Auth $auth,
        UserSessionHandler $userHandler,
        CookieHandler $cookies,
        URI $uri
    ) {
        $this->idpRepo = $em->getRepository(UserIdentityProvider::class);

        $this->auth = $auth;
        $this->userHandler = $userHandler;
        $this->cookies = $cookies;
        $this->uri = $uri;
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        $idp = $this->getSelectedIDP($request);
        if (!$idp instanceof UserIdentityProvider) {
            return $this->withRedirectRoute($response, $this->uri, 'signin');
        }

        $query = $request->getQueryParams();
        $session = $this->getSession($request);

        $data = [
            'code' => $query['code'] ?? '',
            'state' => $query['state'] ?? '',
            'stored_state' => $session->get('external-auth-state') ?: '',
        ];

        $user = $this->auth->authenticate($idp, $data);
        return $this->attemptSignIn($user, $request, $response, $next);
    }

    /**
     * KEEP THIS IN SYNC WITH SignInHandler::attemptSignIn
     * KEEP THIS IN SYNC WITH SignInHandler::attemptSignIn
     * KEEP THIS IN SYNC WITH SignInHandler::attemptSignIn
     *
     * @todo - Combine into a common class that both sign in handlers use.
     *
     * @param User|null $user
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param callable $next
     *
     * @return ResponseInterface
     */
    private function attemptSignIn(?User $user, ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        if (!$user instanceof User) {
            $errs = '';
            foreach ($this->auth->errors() as $field => $errors) {
                $errs .= implode(' ', $errors);
            }

            $this->withFlashError($request, $errs);
            return $this->withRedirectRoute($response, $this->uri, 'signin');
        }

        if ($user->isDisabled()) {
            $this->withFlashError($request, self::ERR_DISABLED);
            return $this->withRedirectRoute($response, $this->uri, 'signin');
        }

        $session = $this->userHandler->startNewSession($request, $user->id());
        // $session->set('is_first_login', $isFirstLogin);
        $session->remove('external-auth-state');

        return $this->withRedirectRoute($response, $this->uri, 'home');
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return UserIdentityProvider|null
     */
    private function getSelectedIDP(ServerRequestInterface $request)
    {
        $selectedIDP = $this->cookies->getCookie($request, self::IDP_COOKIE_NAME);
        if (!$selectedIDP) {
            return null;
        }

        $idp = $this->idpRepo->find($selectedIDP);
        return ($idp instanceof UserIdentityProvider) ? $idp : null;
    }
}
