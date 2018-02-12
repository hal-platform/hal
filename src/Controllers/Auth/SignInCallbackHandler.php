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
use Hal\UI\Controllers\TemplatedControllerTrait;
use Hal\UI\Controllers\SessionTrait;
use Hal\UI\Security\Auth;
use Hal\UI\Security\UserSessionHandler;
use Hal\UI\Security\CSRFManager;
use Hal\UI\Validator\ValidatorErrorTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Panthor\MiddlewareInterface;
use QL\Panthor\HTTP\CookieHandler;
use QL\Panthor\TemplateInterface;
use QL\Panthor\Utility\URI;

class SignInCallbackHandler implements MiddlewareInterface
{
    use SessionTrait;
    use RedirectableControllerTrait;
    use TemplatedControllerTrait;
    use ValidatorErrorTrait;

    private const ERR_INVALID_STATE = 'An error occurred. Please try again.';
    private const ERR_NO_IDP_SELECTED = 'An error occurred. Please try again.';
    private const ERR_CREATING_USER = 'Error finding or creating account. Please try again.';
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
     * @var CSRFManager
     */
    private $csrf;

    /**
     * @var URI
     */
    private $uri;

    /**
     * @param EntityManagerInterface $em
     * @param Auth $auth
     * @param UserSessionHandler $userHandler
     * @param CookieHandler $cookies
     * @param CSRFManager $csrf
     * @param URI $uri
     */
    public function __construct(
        EntityManagerInterface $em,
        Auth $auth,
        UserSessionHandler $userHandler,
        CookieHandler $cookies,
        CSRFManager $csrf,
        URI $uri
    ) {
        $this->idpRepo = $em->getRepository(UserIdentityProvider::class);

        $this->auth = $auth;
        $this->userHandler = $userHandler;
        $this->cookies = $cookies;
        $this->csrf = $csrf;
        $this->uri = $uri;
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        $providers = $this->idpRepo->findAll();
        if (!$providers) {
            return $this->withRedirectRoute($response, $this->uri, 'hal_bootstrap');
        }

        $selectedIDP = $this->getSelectedIDP($request, $providers);
        if (!$selectedIDP instanceof UserIdentityProvider) {
            $this->withFlashError($request, self::ERR_NO_IDP_SELECTED);
            return $this->withRedirectRoute($response, $this->uri, 'signin');
        }

        $query = $request->getQueryParams();
        $code = $query['code'] ?? '';
        $state = $query['state'] ?? '';

        $stateIsValid = $this->csrf->isTokenValid($state, 'oauth.'.$selectedIDP->type());

        if (!$stateIsValid) {
            $this->addError(self::ERR_INVALID_STATE);
            return $next($this->withContext($request, ['errors' => $this->errors()]), $response);
        }

        $user = $this->auth->authenticate($selectedIDP, [
            'code' => $code
        ]);

        if (!$user instanceof User) {
            $this->addError(self::ERR_CREATING_USER);
            return $next($this->withContext($request, ['errors' => $this->errors()]), $response);
        }

        if ($user->isDisabled()) {
            $this->withFlashError($request, self::ERR_DISABLED);
            return $this->withRedirectRoute($response, $this->uri, 'signin');
        }

        $session = $this->userHandler->startNewSession($request, $user->id());
        // $session->set('is_first_login', $isFirstLogin);

        return $this->withRedirectRoute($response, $this->uri, 'home');
    }

    /**
     * @param ServerRequestInterface $request
     * @param array $providers
     *
     * @return UserIdentityProvider|null
     */
    private function getSelectedIDP(ServerRequestInterface $request, array $providers)
    {
        if (count($providers) === 1) {
            return $providers[0];
        }

        // first check if idp in query
        $selectedIDP = $request->getQueryParams()['idp'] ?? '';

        // if not in query, check cookie
        if (!$selectedIDP) {
            $selectedIDP = $this->cookies->getCookie($request, self::IDP_COOKIE_NAME);
        }

        foreach ($providers as $idp) {
            if ($selectedIDP === $idp->id()) {
                return $idp;
            }
        }

        return null;
    }
}
