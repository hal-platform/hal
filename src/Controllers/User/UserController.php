<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\User;

use Doctrine\ORM\EntityManagerInterface;
use MCP\Cache\CachingTrait;
use MCP\Corp\Account\LdapService;
use QL\Hal\Core\Repository\UserRepository;
use QL\Hal\Core\Entity\User;
use QL\Hal\Service\PermissionsService;
use QL\Hal\Service\UserPerm;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use QL\Panthor\Utility\Json;

class UserController implements ControllerInterface
{
    use CachingTrait;

    const CACHE_KEY_COUNTS = 'page:db.job_counts.%s';

    /**
     * @type TemplateInterface
     */
    private $template;

    /**
     * @type User
     */
    private $user;

    /**
     * @type UserRepository
     */
    private $userRepo;

    /**
     * @type LdapService
     */
    private $ldap;

    /**
     * @type PermissionsService
     */
    private $permissions;

    /**
     * @type Json
     */
    private $json;

    /**
     * @param TemplateInterface $template
     * @param User $user
     * @param EntityManagerInterface $em
     *
     * @param LdapService $ldap
     * @param PermissionsService $permissions
     * @param Json $json
     */
    public function __construct(
        TemplateInterface $template,
        User $user,
        EntityManagerInterface $em,
        LdapService $ldap,
        PermissionsService $permissions,
        Json $json
    ) {
        $this->template = $template;
        $this->user = $user;

        $this->userRepo = $em->getRepository(User::CLASS);

        $this->ldap = $ldap;
        $this->permissions = $permissions;
        $this->json = $json;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        $userPerm = $this->permissions->getUserPermissions($this->user);
        $appPerm = $this->permissions->getApplications($userPerm);

        $stats = $this->getCounts();

        $rendered = $this->template->render([
            'user' => $this->user,
            'userPerm' => $userPerm,
            'leadApplications' => $appPerm['lead'],
            'prodApplications' => $appPerm['prod'],
            'nonProdApplications' => $appPerm['non_prod'],

            'ldapUser' => $this->ldap->getUserByCommonId($this->user->id()),

            'builds' => $stats['builds'],
            'pushes' => $stats['pushes']
        ]);
    }

    /**
     * @return array
     */
    private function getCounts()
    {
        $key = sprintf(self::CACHE_KEY_COUNTS, $this->user->id());

        // external cache
        if ($result = $this->getFromCache($key)) {
            $decoded = $this->json->decode($result);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        $data = [
            'builds' => $this->userRepo->getBuildCount($this->user),
            'pushes' => $this->userRepo->getPushCount($this->user),
        ];

        $this->setToCache($key, $this->json->encode($data));
        return $data;
    }
}
