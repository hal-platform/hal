<?php
/**
 * @copyright (c) 2018 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Auth;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Hal\Core\Entity\User;
use Hal\Core\Entity\System\UserIdentityProvider;
use Hal\Core\Type\IdentityProviderEnum;
use Hal\UI\Controllers\CSRFTrait;
use Hal\UI\Controllers\RedirectableControllerTrait;
use Hal\UI\Controllers\SessionTrait;
use Hal\UI\Controllers\TemplatedControllerTrait;
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

    private const ERR_DISABLED = 'Account disabled.';

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
        if (!$idp = $this->sanityCheck($request)) {
            return $next($request, $response);
        }

        $data = $this->auth->prepare($idp, $request);
        if (!$data) {
            $this->importErrors($this->auth->errors());
            return $next($this->withContext($request, ['errors' => $this->errors()]), $response);
        }

        $externalAuthURL = $data['external'] ?? false;
        if ($externalAuthURL) {
            if ($data['state'] ?? false) {
                $this->getSession($request)
                    ->set('external-auth-state', $data['state']);
            }

            return $this->withRedirectAbsoluteURL($response, $externalAuthURL);
        }

        $user = $this->auth->authenticate($idp, $data);
        return $this->attemptSignIn($user, $request, $response, $next);
    }

    /**
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
            $this->importErrors($this->auth->errors());
            return $next($this->withContext($request, ['errors' => $this->errors()]), $response);
        }

        if ($user->isDisabled()) {
            $this->addError(self::ERR_DISABLED);
            return $next($this->withContext($request, ['errors' => $this->errors()]), $response);
        }

        // :( dont know this
        //
        // $isFirstLogin = false;
        // if (!$user instanceof User) {
        //     $isFirstLogin = true;
        // }

        $session = $this->userHandler->startNewSession($request, $user->id());
        // $session->set('is_first_login', $isFirstLogin);

        return $this->withRedirectRoute($response, $this->uri, 'home');
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return UserIdentityProvider|null
     */
    private function sanityCheck(ServerRequestInterface $request)
    {
        if ($request->getMethod() !== 'POST') {
            return null;
        }

        if (!$this->isCSRFValid($request)) {
            return null;
        }

        $idp = $this->getSelectedIDP($request);
        if (!$idp instanceof UserIdentityProvider) {
            return null;
        }

        return $idp;
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return UserIdentityProvider|null
     */
    private function getSelectedIDP(ServerRequestInterface $request)
    {
        $providerID = $request->getParsedBody()['idp'] ?? '';
        if (!$providerID) {
            return null;
        }

        return $this->idpRepo->find($providerID);
    }
}
