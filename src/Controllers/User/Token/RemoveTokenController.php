<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\User\Token;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use QL\Hal\Core\Entity\Token;
use QL\Hal\Core\Entity\User;
use QL\Hal\Flasher;
use QL\Hal\Service\PermissionsService;
use QL\Panthor\Slim\NotFound;
use QL\Panthor\ControllerInterface;

class RemoveTokenController implements ControllerInterface
{
    const SUCCESS = 'Token "%s" has been revoked.';
    const ERR_DENIED = 'You do not have permission to perform this action.';

    /**
     * @type EntityManagerInterface
     */
    private $em;

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
     * @param EntityManagerInterface $em
     * @param PermissionsService $permissions
     * @param User $currentUser
     * @param Flasher $flasher
     * @param NotFound $notFound
     * @param array $parameters
     */
    public function __construct(
        EntityManagerInterface $em,
        PermissionsService $permissions,
        User $currentUser,
        Flasher $flasher,
        NotFound $notFound,
        array $parameters
    ) {
        $this->em = $em;
        $this->tokenRepo = $em->getRepository(Token::CLASS);

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

        if (!$this->isUserAllowed($token->user())) {
            return $this->flasher
                ->withFlash(self::ERR_DENIED, 'error')
                ->load('settings');
        }

        $label = $token->label();

        $this->em->remove($token);
        $this->em->flush();

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
        $perm = $this->permissions->getUserPermissions($this->currentUser);

        if ($perm->isSuper()) {
            return true;
        }

        return ($this->currentUser->id() == $user->id());
    }
}
