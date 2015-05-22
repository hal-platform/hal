<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\User\Token;

use Doctrine\ORM\EntityManager;
use QL\Hal\Core\Repository\UserRepository;
use QL\Hal\Core\Entity\Token;
use QL\Hal\Core\Entity\User;
use QL\Hal\Flasher;
use QL\Hal\Services\PermissionsService;
use QL\Panthor\Slim\NotFound;
use QL\Panthor\ControllerInterface;
use Slim\Http\Request;

class AddTokenController implements ControllerInterface
{
    const SUCCESS = 'Token "%s" created successfully.';
    const ERR_DENIED = 'You do not have permission to perform this action.';
    const ERR_LABEL_REQUIRED = 'Token label is required to create a token.';

    /**
     * @type EntityManager
     */
    private $em;

    /**
     * @type UserRepository
     */
    private $users;

    /**
     * @type Flasher
     */
    private $flasher;

    /**
     * @type PermissionsService
     */
    private $permissions;

    /**
     * @type User
     */
    private $currentUser;

    /**
     * @type Request
     */
    private $request;

    /**
     * @type NotFound
     */
    private $notFound;

    /**
     * @type callable
     */
    private $random;

    /**
     * @type array
     */
    private $parameters;

    /**
     * @param EntityManager $entityManager
     * @param UserRepository $userRepo
     * @param PermissionsService $permissions
     *
     * @param User $user
     * @param Flasher $flasher
     *
     * @param Request $request
     * @param NotFound $notFound
     * @param callable $random
     *
     * @param array $parameters
     */
    public function __construct(
        EntityManager $entityManager,
        UserRepository $userRepo,
        PermissionsService $permissions,

        User $currentUser,
        Flasher $flasher,

        Request $request,
        NotFound $notFound,
        callable $random,

        array $parameters
    ) {
        $this->entityManager = $entityManager;
        $this->userRepo = $userRepo;
        $this->permissions = $permissions;

        $this->currentUser = $currentUser;
        $this->flasher = $flasher;

        $this->request = $request;
        $this->notFound = $notFound;
        $this->random = $random;

        $this->parameters = $parameters;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        $label = $this->request->post('label');
        $id = $this->parameters['id'];

        if (!$user = $this->userRepo->find($id)) {
            return call_user_func($this->notFound);
        }

        if (!$this->isUserAllowed($user)) {
            return $this->flasher
                ->withFlash(self::ERR_DENIED, 'error')
                ->load('settings');
        }

        if (!$label) {
            return $this->flasher
                ->withFlash(self::ERR_LABEL_REQUIRED, 'error')
                ->load('settings');
        }

        $token = $this->generateToken($user, $label);
        $this->flasher
            ->withFlash(sprintf(self::SUCCESS, $token->getLabel()), 'success')
            ->load('settings');
    }

    /**
     * @param User $user
     * @param string $label
     *
     * @return Token
     */
    private function generateToken(User $user, $label)
    {
        $hash = sha1(call_user_func($this->random));

        $token = new Token;
        $token->setValue($hash);
        $token->setUser($user);
        $token->setLabel($label);

        $this->entityManager->persist($token);
        $this->entityManager->flush();

        return $token;
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
