<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\User;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use MCP\Corp\Account\LdapService;
use QL\Hal\Core\Repository\UserRepository;
use QL\Hal\Core\Entity\User;
use QL\Hal\Core\Entity\UserType;
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
     * @type EntityRepository
     */
    private $userTypesRepo;

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
     * @param EntityManagerInterface $em
     * @param PermissionsService $permissions
     * @param Response $response
     * @param NotFound $notFound
     * @param array $parameters
     */
    public function __construct(
        TemplateInterface $template,
        LdapService $ldap,
        EntityManagerInterface $em,
        PermissionsService $permissions,
        Response $response,
        NotFound $notFound,
        array $parameters
    ) {
        $this->template = $template;
        $this->ldap = $ldap;

        $this->userRepo = $em->getRepository(User::CLASS);
        $this->userTypesRepo = $em->getRepository(UserType::CLASS);

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
        if (!$user = $this->userRepo->find($this->parameters['id'])) {
            return call_user_func($this->notFound);
        }

        $rendered = $this->template->render([
            'user' => $user,
            'ldapUser' => $this->ldap->getUserByCommonId($user->getId()),

            'permissions' => $this->permissions->userPushPermissionPairs($user->getHandle()),
            'builds' => $this->userRepo->getBuildCount($user),
            'pushes' => $this->userRepo->getPushCount($user),

            'types' => $this->getUserTypes($user)
        ]);

        $this->response->setBody($rendered);
    }

    /**
     * @param User $user
     *
     * @return array
     */
    private function getUserTypes(User $user)
    {
        $userTypes = $this->userTypesRepo->findBy(['user' => $user]);

        $types = [
            'hasType' => (count($userTypes) > 0),

            'isPleb' => false,
            'isLead' => false,
            'isButtonPusher' => false,
            'isSuper' => false,
            'projects' => []
        ];

        foreach ($userTypes as $t) {
            if ($t->type() === 'lead') {
                $types['isLead'] = true;

                if ($t->application()) {
                    $types['applications'][$t->application()->getId()] = $t->application();
                }

            } elseif ($t->type() === 'btn_pusher') {
                $types['isButtonPusher'] = true;

            } elseif ($t->type() === 'super') {
                $types['isSuper'] = true;

            } elseif ($t->type() === 'pleb') {
                $types['isPleb'] = true;
            }
        }

        return $types;
    }
}
