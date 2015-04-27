<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\User;

use MCP\Corp\Account\LdapService;
use QL\Hal\Core\Repository\UserRepository;
use QL\Hal\Core\Entity\User;
use QL\Hal\Services\PermissionsService;
use QL\Panthor\Slim\NotFound;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use Slim\Http\Response;

class UserController implements ControllerInterface
{
    /**
     * @type TemplateInterface
     */
    private $template;

    /**
     * @type PermissionsService
     */
    private $permissions;

    /**
     * @type LdapService
     */
    private $ldap;

    /**
     * @type UserRepository
     */
    private $userRepo;

    /**
     * @type Response
     */
    private $response;

    /**
     * @type NotFound
     */
    private $notFound;

    /**
     * @type array
     */
    private $parameters;

    /**
     * @param TemplateInterface $template
     * @param LdapService $ldap
     * @param UserRepository $userRepo
     * @param PermissionsService $permissions
     * @param Response $response
     * @param NotFound $notFound
     * @param array $parameters
     */
    public function __construct(
        TemplateInterface $template,
        LdapService $ldap,
        UserRepository $userRepo,
        PermissionsService $permissions,
        Response $response,
        NotFound $notFound,
        array $parameters
    ) {
        $this->template = $template;
        $this->ldap = $ldap;
        $this->userRepo = $userRepo;
        $this->permissions = $permissions;

        $this->response = $response;
        $this->notFound = $notFound;
        $this->parameters = $parameters;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        $id = $this->parameters['id'];

        if (!$user = $this->userRepo->find($id)) {
            return call_user_func($this->notFound);
        }

        $rendered = $this->template->render([
            'user' => $user,
            'ldapUser' => $this->ldap->getUserByCommonId($user->getId()),
            'permissions' => $this->permissions->userPushPermissionPairs($user->getHandle()),
            'builds' => $this->userRepo->getBuildCount($user),
            'pushes' => $this->userRepo->getPushCount($user),
        ]);

        $this->response->setBody($rendered);
    }
}
