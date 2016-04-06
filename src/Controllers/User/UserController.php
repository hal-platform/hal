<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Hal\Controllers\User;

use Doctrine\ORM\EntityManagerInterface;
use MCP\Cache\CachingTrait;
use MCP\Corp\Account\LdapService;
use QL\Hal\Core\Repository\UserRepository;
use QL\Hal\Core\Entity\User;
use QL\Hal\Service\PermissionService;
use QL\Hal\Service\UserPerm;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use QL\Panthor\Utility\Json;

class UserController implements ControllerInterface
{
    use CachingTrait;

    const CACHE_KEY_COUNTS = 'page:db.job_counts.%s';

    /**
     * @var TemplateInterface
     */
    private $template;

    /**
     * @var User
     */
    private $user;

    /**
     * @var UserRepository
     */
    private $userRepo;

    /**
     * @var LdapService
     */
    private $ldap;

    /**
     * @var PermissionService
     */
    private $permissions;

    /**
     * @var Json
     */
    private $json;

    /**
     * @param TemplateInterface $template
     * @param User $user
     * @param EntityManagerInterface $em
     *
     * @param LdapService $ldap
     * @param PermissionService $permissions
     * @param Json $json
     */
    public function __construct(
        TemplateInterface $template,
        User $user,
        EntityManagerInterface $em,
        LdapService $ldap,
        PermissionService $permissions,
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

            'ldapUser' => $this->ldap->getUserByWindowsUsername($this->user->handle()),

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
