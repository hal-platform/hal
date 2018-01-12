<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Hal\Core\Entity\User;
use Hal\Core\Entity\System\UserIdentityProvider;
use Hal\Core\Type\IdentityProviderEnum;
use Hal\UI\Security\Auth;
use Hal\UI\Security\UserSessionHandler;
use Hal\UI\Validator\ValidatorErrorTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Panthor\MiddlewareInterface;
use QL\Panthor\Utility\URI;

class SignInHandler implements MiddlewareInterface
{
    use CSRFTrait;
    use RedirectableControllerTrait;
    use SessionTrait;
    use TemplatedControllerTrait;
    use ValidatorErrorTrait;

    private const ERR_INVALID = 'A username and password must be entered.';
    private const ERR_AUTH_FAILURE = 'Authentication failed.';
    private const ERR_DISABLED = 'Account disabled.';

    const ERR_AUTH_MISCONFIGURED = 'No valid Identity Provider was found. Hal may be misconfigured.';

    /**
     * @var EntityManagerInterface
     */
    private $em;

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
     * @var URI
     */
    private $uri;

    /**
     * @param EntityManagerInterface $em
     * @param Auth $auth
     * @param UserSessionHandler $userHandler
     * @param URI $uri
     */
    public function __construct(
        EntityManagerInterface $em,
        Auth $auth,
        UserSessionHandler $userHandler,
        URI $uri
    ) {
        $this->em = $em;
        $this->idpRepo = $em->getRepository(UserIdentityProvider::class);

        $this->auth = $auth;
        $this->userHandler = $userHandler;
        $this->uri = $uri;
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        if ($request->getMethod() !== 'POST') {
            return $next($request, $response);
        }

        if (!$this->isCSRFValid($request)) {
            return $next($request, $response);
        }

        $providerID = $request->getQueryParams()['idp'] ?? '';
        $redirectURL = $request->getQueryParams()['redirect'] ?? null;

        if (!$providerID) {
            return $next($request, $response);
        }

        $idp = $this->idpRepo->find($providerID);
        if (!$idp instanceof UserIdentityProvider) {
            return $next($request, $response);
        }

        if ($idp->type() === IdentityProviderEnum::TYPE_INTERNAL) {
            $user = $this->auth->authenticate($idp, [
                'username' => $request->getParsedBody()['internal_username'] ?? '',
                'password' => $request->getParsedBody()['internal_password'] ?? '',
            ]);

        } elseif ($idp->type() === IdentityProviderEnum::TYPE_LDAP) {
            $user = $this->auth->authenticate($idp, [
                'username' => $request->getParsedBody()['ldap_username'] ?? '',
                'password' => $request->getParsedBody()['ldap_password'] ?? '',
            ]);

        } else {
            return $next($this->withError($request, self::ERR_AUTH_MISCONFIGURED), $response);
        }

        if (!$user instanceof User) {
            return $next($this->withErrors($request, $this->auth->errors()), $response);
        }

        if ($user->isDisabled()) {
            return $next($this->withError($request, self::ERR_DISABLED), $response);
        }

        // :( dont know this
        //
        // $isFirstLogin = false;
        // if (!$user instanceof User) {
        //     $isFirstLogin = true;
        // }


        $session = $this->userHandler->startNewSession($request, $user->id());
        // $session->set('is_first_login', $isFirstLogin);

        if ($redirectURL && strpos($redirectURL, '/') === 0) {
            return $this->withRedirectURL($response, $request->getUri(), $redirectURL);
        } else {
            return $this->withRedirectRoute($response, $this->uri, 'dashboard');
        }
    }

    /**
     * @param ServerRequestInterface $request
     * @param string $error
     * @param string $field
     *
     * @return ServerRequestInterface
     */
    private function withError(ServerRequestInterface $request, string $error, string $field = '')
    {
        if ($field) {
            $this->addError($error, $field);
        } else {
            $this->addError($error);
        }

        $context = [
            'errors' => $this->errors()
        ];

        return $this->withContext($request, $context);
    }

    /**
     * @param ServerRequestInterface $request
     * @param array $errors
     *
     * @return ServerRequestInterface
     */
    private function withErrors(ServerRequestInterface $request, $errors)
    {
        $context = [
            'errors' => $errors
        ];

        return $this->withContext($request, $context);
    }
}
