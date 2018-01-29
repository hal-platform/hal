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

        $providerID = $request->getParsedBody()['idp'] ?? '';
        if (!$providerID) {
            return $next($request, $response);
        }

        $idp = $this->idpRepo->find($providerID);
        if (!$idp instanceof UserIdentityProvider) {
            return $next($request, $response);
        }

        $data = $this->getFormData($request, $idp);
        $user = $this->auth->authenticate($idp, $data);

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
     * @param UserIdentityProvider $idp
     *
     * @return array
     */
    public function getFormData(ServerRequestInterface $request, UserIdentityProvider $idp): array
    {
        $form = $request->getParsedBody();

        if ($idp->type() === IdentityProviderEnum::TYPE_INTERNAL) {
            return [
                'username' => $form['internal_username'] ?? '',
                'password' => $form['internal_password'] ?? '',
            ];
        }

        if ($idp->type() === IdentityProviderEnum::TYPE_LDAP) {
            return [
                'username' => $form['ldap_username'] ?? '',
                'password' => $form['ldap_password'] ?? '',
            ];
        }

        return [];
    }
}
