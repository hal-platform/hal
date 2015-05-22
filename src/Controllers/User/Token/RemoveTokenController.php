<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\User\Token;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use QL\Hal\Core\Entity\Token;
use QL\Hal\Core\Entity\User;
use QL\Hal\Flasher;
use QL\Hal\Services\PermissionsService;
use QL\Panthor\Slim\NotFound;
use QL\Panthor\ControllerInterface;

class RemoveTokenController implements ControllerInterface
{
    const SUCCESS = 'Token "%s" has been revoked.';
    const ERR_DENIED = 'You do not have permission to perform this action.';

    /**
     * @type EntityManager
     */
    private $entityManager;

    /**
     * @type EntityRepository
     */
    private $tokenRepo;

    /**
     * @type Flasher
     */
    private $flasher;

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
     * @param EntityRepository $tokenRepo
     * @param PermissionsService $permissions
     * @param User $currentUser
     * @param Flasher $flasher
     * @param NotFound $notFound
     * @param array $parameters
     */
    public function __construct(
        EntityManager $entityManager,
        EntityRepository $tokenRepo,
        PermissionsService $permissions,
        User $currentUser,
        Flasher $flasher,
        NotFound $notFound,
        array $parameters
    ) {
        $this->entityManager = $entityManager;
        $this->tokenRepo = $tokenRepo;
        $this->permissions = $permissions;

        $this->currentUser = $currentUser;
        $this->flasher = $flasher;

        $this->notFound = $notFound;
        $this->parameters = $parameters;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        $token = $this->tokenRepo->find($this->parameters['token']);

        if (!$token instanceof Token) {
            return call_user_func($this->notFound);
        }

        if (!$this->isUserAllowed($token->getUser())) {
            return $this->flasher
                ->withFlash(self::ERR_DENIED, 'error')
                ->load('settings');
        }

        $label = $token->getLabel();

        $this->entityManager->remove($token);
        $this->entityManager->flush();

        $this->flasher
            ->withFlash(sprintf(self::SUCCESS, $label), 'success');
            ->load('settings');
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
