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
use Slim\Http\Request;
use Slim\Http\Response;

/**
 * Allow a user to create an API token
 */
class TokenCreateController
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var UserRepository
     */
    private $users;

    /**
     * @var UrlHelper
     */
    private $url;

    /**
     * @var PermissionsService
     */
    private $permissions;

    /**
     * @var User
     */
    private $currentUser;

    /**
     * @param EntityManager $entityManager
     * @param UserRepository $userRepo
     * @param UrlHelper $url
     * @param PermissionsService $permissions
     * @param User $user
     */
    public function __construct(
        EntityManager $entityManager,
        UserRepository $userRepo,
        UrlHelper $url,
        PermissionsService $permissions,
        User $currentUser
    ) {
        $this->entityManager = $entityManager;
        $this->userRepo = $userRepo;
        $this->url = $url;
        $this->permissions = $permissions;

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
        $label = $request->post('label', null);
        $id = $params['id'];

        if (!$user = $this->userRepo->find($id)) {
            return call_user_func($notFound);
        }

        if (!$this->isUserAllowed($user)) {
            return $this->url->redirectFor('denied');
        }

        if ($label) {
            $token = $this->generateToken($user, $label);
        }

        $this->url->redirectFor('user.edit', ['id' => $id], [], 303);
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
