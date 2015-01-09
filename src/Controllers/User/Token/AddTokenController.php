<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\User\Token;

use Doctrine\ORM\EntityManager;
use QL\Hal\Core\Entity\Repository\UserRepository;
use QL\Hal\Core\Entity\Token;
use QL\Hal\Core\Entity\User;
use QL\Hal\Helpers\UrlHelper;
use QL\Hal\Services\PermissionsService;
use QL\Hal\Slim\NotFound;
use QL\Panthor\ControllerInterface;
use Slim\Http\Request;

/**
 * Allow a user to create an API token
 */
class AddTokenController implements ControllerInterface
{
    /**
     * @type EntityManager
     */
    private $em;

    /**
     * @type UserRepository
     */
    private $users;

    /**
     * @type UrlHelper
     */
    private $url;

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
     * @type array
     */
    private $parameters;

    /**
     * @param EntityManager $entityManager
     * @param UserRepository $userRepo
     * @param UrlHelper $url
     * @param PermissionsService $permissions
     * @param User $user
     * @param Request $request
     * @param NotFound $notFound
     * @param array $parameters
     */
    public function __construct(
        EntityManager $entityManager,
        UserRepository $userRepo,
        UrlHelper $url,
        PermissionsService $permissions,
        User $currentUser,
        Request $request,
        NotFound $notFound,
        array $parameters
    ) {
        $this->entityManager = $entityManager;
        $this->userRepo = $userRepo;
        $this->url = $url;
        $this->permissions = $permissions;

        $this->currentUser = $currentUser;

        $this->request = $request;
        $this->notFound = $notFound;
        $this->parameters = $parameters;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        $label = $this->request->post('label', null);
        $id = $this->parameters['id'];

        if (!$user = $this->userRepo->find($id)) {
            return call_user_func($this->notFound);
        }

        if (!$this->isUserAllowed($user)) {
            return $this->url->redirectFor('denied');
        }

        if ($label) {
            $token = $this->generateToken($user, $label);
        }

        $this->url->redirectFor('settings');
    }

    /**
     * @param User $user
     * @param string $label
     *
     * @return Token
     */
    private function generateToken(User $user, $label)
    {
        $hash = sha1(mt_rand());

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
