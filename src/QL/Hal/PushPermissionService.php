<?php
/**
 * @copyright ©2013 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal;

use MCP\Corp\Account\LdapService;
use MCP\Corp\Account\User;
use QL\Hal\Core\Entity\Repository\RepositoryRepository;
use QL\Hal\Core\Entity\Repository\UserRepository;
use QL\Hal\Core\Entity\Repository\DeploymentRepository;
use QL\Hal\Core\Entity\Repository;
use QL\Hal\Services\GithubService;
use Zend\Ldap\Dn;

/**
 * @api
 *
 * @todo REFACTOR THIS CLASS, lots of bad
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
     * @var int
     */
    private $godModeOverride;

    /**
     * @var bool
     */
    private $authed;

    private $repoRepo;

    private $userRepo;

    private $deployRepo;

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
     *  @param LdapService $ldapService
     *  @param DeploymentRepository $deployRepo
     *  @param RepositoryRepository $repoRepo
     *  @param UserRepository $userRepo
     *  @param GithubService $github
     *  @param $godModeOverride
     */
    public function __construct(
        LdapService $ldapService,
        DeploymentRepository $deployRepo,
        RepositoryRepository $repoRepo,
        UserRepository $userRepo,
        GithubService $github,
        $godModeOverride
    ) {
        $this->ldapService = $ldapService;
        $this->deployRepo = $deployRepo;
        $this->userRepo = $userRepo;
        $this->repoRepo = $repoRepo;
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
        if ($this->isUserKeymaster($user)) {
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

        $permissions = array();

        foreach ($this->listEnvRepoPairs() as $repo => $envs) {
            foreach ($envs as $env) {
                if ($this->canUserPushToEnvRepo($user, $repo, $env)) {
                    $permissions[] = array($repo, $env);
                }
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
        $users = $this->userRepo->findAll();
        $repo = $this->repoRepo->findOneBy(['key' => $repo]);
        $pairs = $this->listEnvRepoPairs($repo);

        $permissions = array();

        foreach ($users as $user) {

            foreach ($pairs as $env) {

                if ($this->canUserPushToEnvRepo($user->getHandle(), $repo, $env)) {
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
     *  Check if the user is in the keymasters group
     *
     *  @param $user
     *  @return bool
     */
    public function isUserKeymaster($user)
    {
        // allow user passing as string
        if (!($user instanceof User)) {
            $user = $this->ldapService->getUserByWindowsUsername($user);
        }

        if ($this->ldapUserInGroupCache(self::getDn(self::PERM_DN_KEYMASTERS), $user->dn())) {
            return true;
        }

        return false;
    }

    /**
     * @var $user
     * @return boolean
     */
    public function isUserAdmin($user)
    {
        // allow user passing as string
        if (!($user instanceof User)) {
            $user = $this->ldapService->getUserByWindowsUsername($user);
        }

        if ($user->commonId() == $this->godModeOverride) {
            return true;
        }

        return $this->ldapUserInGroupCache(self::dnForAdminGroup(), $user->dn());
    }

    /**
     * @var User $user
     * @var string $repo
     * @return boolean
     */
    public function isUserCollaborator(User $user, $repo)
    {
        $repo = $this->repoRepo->findOneBy(['key' => $repo]);

        return $this->github->isUserCollaborator(
            $repo->getGithubUser(),
            $repo->getGithubRepo(),
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

    /**
     *  Get an array of all repo:env pairs
     *
     *  Implementation is really bad. Let's refactor this at some point.
     *
     *  @param Repository $repo
     *  @return array
     */
    private function listEnvRepoPairs(Repository $repo = null)
    {
        // revise this later to not pull in all the entities, use partials
        if ($repo) {
            $deploys = $this->deployRepo->findBy(['repository' => $repo]);
        } else {
            $deploys = $this->deployRepo->findAll();
        }

        $pairs = [];

        foreach ($deploys as $deploy) {
            $repo = $deploy->getRepository()->getKey();
            $env = $deploy->getServer()->getEnvironment()->getKey();

            if (isset($pairs[$repo])) {
                $pairs[$repo][$env] = $env;
            } else {
                $pairs[$repo] = [];
            }
        }

        return $pairs;
    }
}
