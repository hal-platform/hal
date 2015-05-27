<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Services;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use MCP\Corp\Account\LdapService;
use MCP\Corp\Account\User as LdapUser;
use MCP\Cache\CachingTrait;
use QL\Hal\Core\Entity\Deployment;
use QL\Hal\Core\Entity\Environment;
use QL\Hal\Core\Entity\Repository;
use QL\Hal\Core\Entity\User;
use Zend\Ldap\Dn;

class PermissionsService
{
    use CachingTrait;

    const CACHE_COLLAB = 'permissions:github.%s.%s';
    const CACHE_LDAP_GROUP = 'permissions:ldap.group.%s';
    const CACHE_LDAP_USER = 'permissions:ldap.user.%s';

    /**
     * Key Masters Group
     */
    const DN_KEYMASTER = 'CN=git-admin-prod,OU=GIT,DC=mi,DC=corp';

    /**
     * Super Admin Group (Web Core)
     */
    const DN_SUPER_ADMIN = 'CN=git-hal,OU=GIT,DC=mi,DC=corp';

    /**
     * Repository Admin Group
     */
    const DN_REPOSITORY_ADMIN = 'CN=git-admin,OU=GIT,DC=mi,DC=corp';

    /**
     * Repository Permission Groups
     *
     * - Can push the specified repository in the specified environment.
     */
    const DN_REPOSITORY = 'CN=git-%s-%s,OU=GIT,DC=mi,DC=corp';

    /**
     * @var LdapService
     */
    private $ldap;

    /**
     * @var EntityRepository
     */
    private $deploymentRepo;
    private $repoRepo;
    private $userRepo;
    private $environmentRepo;

    /**
     * @var GithubService
     */
    private $github;

    /**
     * @var array
     */
    private $productionEnvironments;

    /**
     * @var array
     */
    private $halRepositories;

    /**
     * @param LdapService $ldap
     * @param EntityManagerInterface $em
     * @param GithubService $github
     * @param string $god
     */
    public function __construct(
        LdapService $ldap,
        EntityManagerInterface $em,
        GithubService $github,
        array $productionEnvironments,
        array $halRepositories
    ) {
        $this->ldap = $ldap;

        $this->deploymentRepo = $em->getRepository(Deployment::CLASS);
        $this->repoRepo = $em->getRepository(Repository::CLASS);
        $this->userRepo = $em->getRepository(User::CLASS);
        $this->environmentRepo = $em->getRepository(Environment::CLASS);

        $this->github = $github;
        $this->productionEnvironments = $productionEnvironments;
        $this->halRepositories = $halRepositories;
    }

    ####################################################################################################################
    # PERMISSION GROUPS
    ####################################################################################################################

    /**
     * Check if a user is a Super Admin
     *
     * - View the Super Admin page
     * - All lower access level permissions
     *
     * @param LdapUser|string $user
     * @return bool
     */
    public function allowSuperAdmin($user)
    {
        if (!($user = $this->getUser($user)) instanceof LdapUser) {
            // user not found in ldap
            return false;
        }

        // Super Admin LDAP Group
        if ($this->isUserInGroup($user, $this->generateDn(self::DN_SUPER_ADMIN))) {
            return true;
        }

        return false;
    }

    /**
     * Check if a user is an Admin
     *
     * - View the Admin page
     * - Create, edit, and delete all entities
     * - All lower access level permissions
     *
     * @param LdapUser|string $user
     * @return bool
     */
    public function allowAdmin($user)
    {
        if (!($user = $this->getUser($user)) instanceof LdapUser) {
            // user not found in ldap
            return false;
        }

        // Inherit users from Super Admin group
        if ($this->allowSuperAdmin($user)) {
            return true;
        }

        // Key Master LDAP Group
        if ($this->isUserInGroup($user, $this->generateDn(self::DN_KEYMASTER))) {
            return true;
        }

        return false;
    }

    /**
     * Check if a user is a Repository Admin
     *
     * - Edit repository details
     * - Edit repository deployments
     *
     * @param LdapUser|string $user
     * @param string $repo
     * @return bool
     */
    public function allowRepoAdmin($user, $repo)
    {
        if (!($user = $this->getUser($user)) instanceof LdapUser) {
            // user not found in ldap
            return false;
        }

        // Inherit users from Admin group
        if ($this->allowAdmin($user)) {
            return true;
        }

        // Repository Admin LDAP group
        if ($this->isUserInGroup($user, $this->generateDn(self::DN_REPOSITORY_ADMIN)) && $this->userHasRepoPermission($user, $repo)) {
            return true;
        }

        return false;
    }

    ####################################################################################################################
    # ACTION PERMISSIONS
    ####################################################################################################################

    /**
     * Check if a user is allowed to build a given repository
     *
     * @param string $user
     * @param string $repository
     * @return bool
     */
    public function allowBuild($user, $repository)
    {
        if (!($user = $this->getUser($user)) instanceof LdapUser) {
            // user not found in ldap
            return false;
        }

        if ($this->allowAdmin($user)) {
            return true;
        }

        if ($this->userHasRepoPermission($user, $repository)) {
            return true;
        }

        return false;
    }

    /**
     * Check if a user is allowed to push to a given repository:environment pair
     *
     * @param LdapUser|string $user
     * @param string $repository
     * @param string $environment
     * @return bool
     */
    public function allowPush($user, $repository, $environment)
    {
        if (!($user = $this->getUser($user)) instanceof LdapUser) {
            // user not found in ldap
            return false;
        }

        // Custom permissions for Super Admins
        if ($this->allowSuperAdmin($user)) {
            // HAL 9000 Exception
            if (in_array($repository, $this->halRepositories)) {
                return true;
            }

            // Super Admins can push any repo to non-prod deployments
            if (!$this->isEnvironmentProduction($environment)) {
                return true;
            }
        }

        $isKeymaster = $this->isUserInGroup($user, $this->generateDn(self::DN_KEYMASTER));
        if ($isKeymaster) {
            return true;
        }

        // Production Exception
        if ($this->isEnvironmentProduction($environment)) {
            return $isKeymaster;
        }

        if ($this->userHasRepoPermission($user, $repository, $environment)) {
            return true;
        }

        return false;
    }

    ####################################################################################################################
    # ANALYTICS
    ####################################################################################################################

    /**
     * Whether to show analytics or not
     *
     * @param $user
     * @return bool
     */
    public function showAnalytics($user)
    {
        if (!($user = $this->getUser($user)) instanceof LdapUser) {
            // user not found in ldap
            return false;
        }

        if ($this->allowSuperAdmin($user)) {
            return false;
        }

        return true;
    }

    ####################################################################################################################
    # PERMISSION PAIR QUERIES
    ####################################################################################################################

    /**
     * Get a list of repositories a user can build
     *
     * @param $user
     * @return array
     */
    public function userRepositories($user)
    {
        if (!($user = $this->getUser($user)) instanceof LdapUser) {
            // user not found in ldap
            return false;
        }

        $repositories = [];

        foreach ($this->repoRepo->findBy([], ['key' => 'ASC']) as $repo) {
            if ($this->allowBuild($user, $repo->getKey())) {
                $repositories[] = $repo;
            }
        }

        return $repositories;
    }

    /**
     * Get all push permission pairs for a user
     *
     * @param $user
     * @return array
     */
    public function userPushPermissionPairs($user)
    {
        if (!($user = $this->getUser($user)) instanceof LdapUser) {
            // user not found in ldap
            return false;
        }

        $permissions = [];

        foreach ($this->getPermissionPairs() as $pair) {
            if ($this->allowPush($user, $pair['repository']->getKey(), $pair['environment']->getKey())) {
                $permissions[$pair['environment']->getKey()][] = $pair;
            }
        }

        return $permissions;
    }

    /**
     * Get all build permission pairs for a user
     *
     * @param $user
     * @return array
     */
    public function userBuildPermissionPairs($user)
    {
        $user = $this->getUser($user);

        if (!($user instanceof LdapUser)) {
            // user not found in ldap
            return [];
        }

        $permissions = [];

        foreach ($this->getPermissionPairs() as $pair) {
            if ($this->allowBuild($user, $pair['repository']->getKey(), $pair['environment']->getKey())) {
                $permissions[$pair['environment']->getKey()][] = $pair;
            }
        }

        return $permissions;
    }

    /**
     * Get all permission pairs for a repository
     *
     * @param string $repository The repository key
     * @return array
     */
    public function repositoryPermissionPairs($repository)
    {
        $repository = $this->repoRepo->findOneBy(['key' => $repository]);
        $users = $this->userRepo->findBy(['isActive' => true], ['name' => 'ASC']);

        $permissions = [];

        foreach ($this->getPermissionPairs($repository) as $pair) {
            foreach ($users as $user) {
                if ($this->allowPush($user, $pair['repository']->getKey(), $pair['environment']->getKey())) {
                    $permissions[$pair['environment']->getKey()][] = [
                        'user' => $user,
                        'environment' => $pair['environment']
                    ];
                }
            }
        }

        return $permissions;
    }

    ####################################################################################################################
    # PERMISSION HELPERS
    ####################################################################################################################

    /**
     * Check if a user has been granted permission to a repository. When $env is null, checks to see if a user has been
     * granted permission to ANY environment. Otherwise, checks a specific environment.
     *
     * @param LdapUser $user
     * @param string $repo
     * @param string $env
     * @return bool
     */
    private function userHasRepoPermission(LdapUser $user, $repo, $env = null)
    {
        if ($env) {

            // Specific LDAP Repository:Environment Group
            if ($this->isUserInGroup($user, $this->generateDn(sprintf(self::DN_REPOSITORY, $repo, $env)))) {
                return true;
            }

            // Github Collaborator (Non-Production)
            if (!$this->isEnvironmentProduction($env) && $this->isUserCollaborator($user, $repo)) {
                return true;
            }

        } else {

            // Any LDAP Repository Group
            foreach ($this->environmentRepo->findAll() as $environment) {
                if ($this->allowPush($user, $repo, $environment->getKey())) {
                    return true;
                }
            }

            // Github Collaborator
            if ($this->isUserCollaborator($user, $repo)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param Repository $repository
     * @return array
     */
    private function getPermissionPairs(Repository $repository = null)
    {
        if ($repository) {
            $deployments = $this->deploymentRepo->findBy(['repository' => $repository]);
        } else {
            $deployments = $this->deploymentRepo->findAll();
        }

        $pairs = [];

        foreach ($deployments as $deployment) {
            $environment = $deployment->getServer()->getEnvironment();
            $repository = $deployment->getRepository();

            $pairs[md5($environment->getId().$repository->getId())] = [
                'environment' => $environment,
                'repository' => $repository
            ];
        }

        return array_values($pairs);
    }

    /**
     * Check if an environment is a production environment
     *
     * @param string $environment
     * @return bool
     */
    private function isEnvironmentProduction($environment)
    {
        return in_array($environment, $this->productionEnvironments);
    }

    ####################################################################################################################
    # GITHUB QUERIES
    ####################################################################################################################

    /**
     * Check if a user is a collaborator on a Github repository
     *
     * @param LdapUser $user
     * @param $repository
     * @return bool
     */
    private function isUserCollaborator(LdapUser $user, $repository)
    {
        $key = sprintf(self::CACHE_COLLAB, $user->commonId(), $repository);
        if ($result = $this->getFromCache($key)) {
            return $result;
        }

        $repository = $this->repoRepo->findOneBy(['key' => $repository]);

        $result = $this->github->isUserCollaborator(
            $repository->getGithubUser(),
            $repository->getGithubRepo(),
            $user->windowsUsername()
        );

        $this->setToCache($key, $result);
        return $result;
    }

    ####################################################################################################################
    # LDAP QUERIES
    ####################################################################################################################

    /**
     * Get an LDAP user by Windows Username
     *
     * @param string|LdapUser|User $user
     * @return LdapUser|null
     */
    private function getUser($user)
    {
        if (!$user) {
            return null;
        }

        if ($user instanceof LdapUser) {
            return $user;
        }

        if ($user instanceof User) {
            $user = $user->getHandle();
        }

        $key = sprintf(self::CACHE_LDAP_USER, $user);

        if ($result = $this->getFromCache($key)) {
            return $result;
        }

        $ldapUser = $this->ldap->getUserByWindowsUsername($user);

        if ($ldapUser instanceof LdapUser) {
            $this->setToCache($key, $ldapUser);
            return $ldapUser;
        }

        return null;
    }

    /**
     * Check if a user is in an LDAP group
     *
     * @param LdapUser $user
     * @param Dn $group
     * @return bool
     */
    private function isUserInGroup(LdapUser $user, Dn $group)
    {
        $users = $this->usersInGroup($group);

        // @todo Improve userInGroup lookup (MCP improvement?)
        foreach ($users as $member) {
            if ($member->commonId() == $user->commonId()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get an array of all users in an LDAP group
     *
     * @param Dn $group
     * @return LdapUser[]
     */
    private function usersInGroup(Dn $group)
    {
        $key = sprintf(self::CACHE_LDAP_GROUP, md5($group));

        if ($result = $this->getFromCache($key)) {
            return $result;
        }

        $users = $this->ldap->usersInGroup($group);

        $this->setToCache($key, $users);
        return $users;
    }

    /**
     * Generate an arbitrary LDAP DN
     *
     * @param string $query
     * @return Dn
     */
    private function generateDn($query)
    {
        return Dn::fromString($query);
    }
}
