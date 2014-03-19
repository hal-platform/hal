<?php
/**
 * @copyright ©2013 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal;

use MCP\Corp\Account\LdapService;
use MCP\Corp\Account\User;
use QL\Hal\Services\EnvironmentService;
use QL\Hal\Services\RepositoryService;
use Zend\Ldap\Dn;
use QL\Hal\Services\UserService;

/**
 * @api
 */
class PushPermissionService
{
    const PERM_DN_TPL       = 'CN=git-%s-%s,OU=GIT,DC=mi,DC=corp';
    const PERM_DN_ADMIN     = 'CN=git-admin,OU=GIT,DC=mi,DC=corp';
    const LDAP_USER         = 'placeholder';
    const LDAP_PASSWORD     = 'placeholder';

    /**
     * @var LdapService
     */
    private $ldapService;

    /**
     * @var RepositoryService
     */
    private $repoService;

    /**
     * @var int
     */
    private $godModeOverride;

    /**
     * @var bool
     */
    private $authed;

    /**
     * @var Services\UserService
     */
    private $userService;

    private $cache;

    /**
     * @return Dn
     */
    public static function dnForAdminGroup()
    {
        return Dn::fromString(self::PERM_DN_ADMIN);
    }

    /**
     * @param string $repoShortName
     * @param string $envShortName
     * @return Dn
     */
    public static function getDnForPermGroup($repoShortName, $envShortName)
    {
        return Dn::fromString(sprintf(self::PERM_DN_TPL, $repoShortName, $envShortName));
    }

    /**
     *  Constructor
     *
     *  @param LdapService $ldapService
     *  @param RepositoryService $repoService
     *  @param UserService $userService
     *  @param int $godModeOverride
     */
    public function __construct(
        LdapService $ldapService,
        RepositoryService $repoService,
        UserService $userService,
        $godModeOverride
    ) {
        $this->ldapService = $ldapService;
        $this->repoService = $repoService;
        $this->userService = $userService;
        $this->godModeOverride = $godModeOverride;
        $this->authed = false;
        $this->cache = array();
    }

    /**
     *  Check to see if a user can push a repo to a given environment
     *
     *  CANONICAL SOURCE FOR USER REPO:ENV PERMISSIONS
     *
     *  @param User|string $user
     *  @param string $repo
     *  @param string $env
     *  @return bool
     */
    public function canUserPushToEnvRepo($user, $repo, $env)
    {
        $this->checkAuthenticated();

        // allow user passing as string
        if (!($user instanceof User)) {
            $user = $this->ldapService->getUserByWindowsUsername($user);
        }

        // admin push whitelist
        if ($this->isUserAdmin($user) && (in_array($env, array('test', 'beta')) || $repo == 'hal9000')) {
            return true;
        }

        $group = self::getDnForPermGroup($repo, $env);

        //return $this->ldapService->isUserInGroup($group, $user->dn());
        return $this->ldapUserInGroupCache($group, $user->dn());
    }

    /**
     *  Get an array of all repo:env pairs a given user can push to
     *
     *  @param string $commonId
     *  @return array
     */
    public function repoEnvsCommonIdCanPushTo($commonId)
    {
        $this->checkAuthenticated();
        $user = $this->ldapService->getUserByCommonId($commonId);
        $pairs = $this->repoService->listRepoEnvPairs();

        $permissions = array();

        foreach ($pairs as $pair) {
            if ($this->canUserPushToEnvRepo($user, $pair['RepShortName'], $pair['EnvShortName'])) {
                $permissions[] = array($pair['RepShortName'], $pair['EnvShortName']);
            }
        }

        return $permissions;
    }

    /**
     *  Get an array of all user:env access pairs for a given repo
     *
     *  @param $repo
     *  @return array
     */
    public function allUsersWithAccess($repo)
    {
        $users = $this->userService->listAll();
        $pairs = $this->repoService->listRepoEnvPairs($repo);

        $permissions = array();

        foreach ($users as $user) {
            $username = $user['UserName'];

            foreach ($pairs as $pair) {

                $repo = $pair['RepShortName'];
                $env = $pair['EnvShortName'];

                if ($this->canUserPushToEnvRepo($username, $repo, $env)) {
                    $permissions[] = array(
                        'user' => $user,
                        'repo' => $repo,
                        'env' => $env
                    );
                }
            }
        }

        return $permissions;
    }

    public function checkAuthenticated()
    {
        if ($this->authed) {
            return;
        }
        $this->ldapService->authenticate(self::LDAP_USER, self::LDAP_PASSWORD, false);
    }

    public function isUserAdmin(User $user)
    {
        $this->checkAuthenticated();
        if ($user->commonId() == $this->godModeOverride) {
            return true;
        }
        //return $this->ldapService->isUserInGroup(self::dnForAdminGroup(), $user->dn());
        return $this->ldapUserInGroupCache(self::dnForAdminGroup(), $user->dn());
    }

    /**
     *  Provide quick and dirty memory cache for LDAP user in group queries
     *
     *  This is dump. Refactor later.
     *
     *  @param string $group
     *  @param string $user
     *  @return mixed
     */
    public function ldapUserInGroupCache($group, $user)
    {
        $key = md5($group.$user);

        if (array_key_exists($key, $this->cache)) {
            return $this->cache[$key];
        }

        $this->checkAuthenticated();
        $result = $this->ldapService->isUserInGroup($group, $user);
        $this->cache[$key] = $result;

        return $result;
    }
}
