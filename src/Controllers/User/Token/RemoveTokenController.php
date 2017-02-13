<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\User\Token;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Hal\UI\Flasher;
use Hal\UI\Service\PermissionService;
use QL\Hal\Core\Entity\Token;
use QL\Hal\Core\Entity\User;
use QL\Panthor\Slim\NotFound;
use QL\Panthor\ControllerInterface;

class RemoveTokenController implements ControllerInterface
{
    const SUCCESS = 'Token "%s" has been revoked.';
    const ERR_DENIED = 'You do not have permission to perform this action.';

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var EntityRepository
     */
    private $tokenRepo;

    /**
     * @var Flasher
     */
    private $flasher;

    /**
     * @var PermissionService
     */
    private $permissions;

    /**
     * @var LdapUser
     */
    private $currentUser;

    /**
     * @var NotFound
     */
    private $notFound;

    /**
     * @var array
     */
    private $parameters;

    /**
     * @param EntityManagerInterface $em
     * @param PermissionService $permissions
     * @param User $currentUser
     * @param Flasher $flasher
     * @param NotFound $notFound
     * @param array $parameters
     */
    public function __construct(
        EntityManagerInterface $em,
        PermissionService $permissions,
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
            ->withFlash(sprintf(self::SUCCESS, $label), 'success')
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
