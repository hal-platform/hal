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
use QL\Hal\Slim\NotFound;
use QL\Panthor\ControllerInterface;

/**
 * Allow a user to delete an API token
 */
class RemoveTokenController implements ControllerInterface
{
    /**
     * @type EntityManager
     */
    private $entityManager;

    /**
     * @type TokenRepository
     */
    private $tokenRepo;

    /**
     * @type UrlHelper
     */
    private $url;

    /**
     * @type PermissionsService
     */
    private $permissions;

    /**
     * @type LdapUser
     */
    private $currentUser;

    /**
     * @type NotFound
     */
    private $notFound;

    /**
     * @type array
     */
    private $parameters;

    /**
     * @param EntityManager $entityManager
     * @param TokenRepository $tokenRepo
     * @param UrlHelper $url
     * @param PermissionsService $permissions
     * @param User $currentUser
     * @param NotFound $notFound
     * @param array $parameters
     */
    public function __construct(
        EntityManager $entityManager,
        TokenRepository $tokenRepo,
        UrlHelper $url,
        PermissionsService $permissions,
        User $currentUser,
        NotFound $notFound,
        array $parameters
    ) {
        $this->entityManager = $entityManager;
        $this->tokenRepo = $tokenRepo;
        $this->permissions = $permissions;
        $this->url = $url;
        $this->currentUser = $currentUser;

        $this->notFound = $notFound;
        $this->parameters = $parameters;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        $token = $this->tokenRepo->findOneBy(['value' => $this->parameters['token']]);

        if (!$token instanceof Token) {
            return call_user_func($this->notFound);
        }

        if ($this->isUserAllowed($token->getUser())) {
            $this->entityManager->remove($token);
            $this->entityManager->flush();
        }

        $this->url->redirectFor('settings');
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
