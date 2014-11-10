<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\User\Token;

use Doctrine\ORM\EntityManager;
use QL\Hal\Core\Entity\Repository\TokenRepository;
use QL\Hal\Core\Entity\Token;
use QL\Hal\Core\Entity\User;
use QL\Hal\Helpers\UrlHelper;
use QL\Hal\Services\PermissionsService;
use Slim\Http\Request;
use Slim\Http\Response;

/**
 * Allow a user to delete an API token
 */
class TokenDeleteController
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var TokenRepository
     */
    private $tokenRepo;

    /**
     * @var UrlHelper
     */
    private $url;

    /**
     * @var PermissionsService
     */
    private $permissions;

    /**
     * @var LdapUser
     */
    private $currentUser;

    /**
     * @param EntityManager $entityManager
     * @param TokenRepository $tokenRepo
     * @param UrlHelper $url
     * @param PermissionsService $permissions
     * @param User $currentUser
     */
    public function __construct(
        EntityManager $entityManager,
        TokenRepository $tokenRepo,
        UrlHelper $url,
        PermissionsService $permissions,
        User $currentUser
    ) {
        $this->entityManager = $entityManager;
        $this->tokenRepo = $tokenRepo;
        $this->permissions = $permissions;
        $this->url = $url;
        $this->currentUser = $currentUser;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $params
     * @param callable $notFound
     */
    public function __invoke(Request $request, Response $response, array $params = null, callable $notFound = null)
    {
        $token = $this->tokenRepo->findOneBy(['value' => $params['token']]);

        if (!$token instanceof Token) {
            return call_user_func($notFound);
        }

        if (!$this->isUserAllowed($token->getUser())) {
            // no permission to delete, silently fail for the baddy
            return $this->url->redirectFor('user.edit', ['id' => $params['id']], [], 303);
        }

        $this->entityManager->remove($token);
        $this->entityManager->flush();

        $this->url->redirectFor('user.edit', ['id' => $params['id']], [], 303);
    }

    /**
     * Does the user have the correct permissions to access this page?
     *
     * @param User $user
     * @return boolean
     */
    private function isUserAllowed(User $user)
    {
        if ($this->permissions->allowAdmin($this->currentUser)) {
            return true;
        }

        return ($this->currentUser->getId() == $user->getId());
    }
}
