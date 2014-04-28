<?php
/**
 * @copyright ©2013 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal;

use MCP\Corp\Account\LdapService;
use MCP\Corp\Account\User;
use QL\Hal\Services\GithubService;
use QL\Hal\Services\RepositoryService;
use QL\Hal\Services\UserService;
use Zend\Ldap\Dn;

/**
 * @api
 */
class PushPermissionService
{
    const PERM_DN_TPL           = 'CN=git-%s-%s,OU=GIT,DC=mi,DC=corp';
    const PERM_DN_KEYMASTERS    = 'CN=git-admin-prod,OU=GIT,DC=mi,DC=corp';
    const PERM_DN_ADMIN         = 'CN=git-admin,OU=GIT,DC=mi,DC=corp';

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

    /**
     * @var GithubService
     */
    private $github;

    /**
     * @var array
     */
    private $cache;

    /**
     * @var string[]
     */
    private $productionEnvironments;

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
     *  Convert DN string to DN object
     *
     *  @param string $group
     *  @return Dn
     */
    public static function getDn($group)
    {
        return Dn::fromString($group);
    }

    /**
     *  Constructor
     *
     *  @param LdapService $ldapService
     *  @param RepositoryService $repoService
     *  @param UserService $userService
     *  @param GithubService $github
     *  @param int $godModeOverride
     */
    public function __construct(
        LdapService $ldapService,
        RepositoryService $repoService,
        UserService $userService,
        GithubService $github,
        $godModeOverride
    ) {
        $this->ldapService = $ldapService;
        $this->repoService = $repoService;
        $this->userService = $userService;
        $this->github = $github;
        $this->godModeOverride = $godModeOverride;

        $this->authed = false;
        $this->cache = array();

        // @todo make this an environment specific flag such as env type
        $this->productionEnvironments = ['prod'];
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
        // allow user passing as string
        if (!($user instanceof User)) {
            $user = $this->ldapService->getUserByWindowsUsername($user);
        }

        $inProd = in_array($env, $this->productionEnvironments);

        // hal-admin push whitelist
        if ($this->isUserAdmin($user) && (!$inProd || $repo == 'hal9000')) {
            return true;
        }

        // keymasters whitelist for any environment
        if ($this->ldapUserInGroupCache(self::getDn(self::PERM_DN_KEYMASTERS), $user->dn())) {
            return true;
        }

        // repository collaborators in lower environments
        if (!$inProd && $this->isUserCollaborator($user, $repo)) {
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

    /**
     * @var User $user
     * @return boolean
     */
    public function isUserAdmin(User $user)
    {
        if ($user->commonId() == $this->godModeOverride) {
            return true;
        }
        //return $this->ldapService->isUserInGroup(self::dnForAdminGroup(), $user->dn());
        return $this->ldapUserInGroupCache(self::dnForAdminGroup(), $user->dn());
    }

    /**
     * @var User $user
     * @var string $repo
     * @return boolean
     */
    public function isUserCollaborator(User $user, $repo)
    {
        $repo = $this->repoService->getFromName($repo);

        return $this->github->isUserCollaborator(
            $repo['GithubUser'],
            $repo['GithubRepo'],
            $user->windowsUsername()
        );
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

        $result = $this->ldapService->isUserInGroup($group, $user);
        $this->cache[$key] = $result;

        return $result;
    }
}
