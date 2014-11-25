<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Services;

use RuntimeException;
use MCP\Corp\Account\LdapService;
use MCP\Corp\Account\User as LdapUser;
use MCP\Cache\CachingTrait;
use QL\Hal\Core\Entity\Repository;
use QL\Hal\Core\Entity\Repository\DeploymentRepository;
use QL\Hal\Core\Entity\Repository\EnvironmentRepository;
use QL\Hal\Core\Entity\Repository\RepositoryRepository;
use QL\Hal\Core\Entity\Repository\UserRepository;
use QL\Hal\Core\Entity\User as EntityUser;
use Zend\Ldap\Dn;

class PermissionsService
{
    use CachingTrait;

    const CACHE_COLLAB = 'permissions:github.%s.%s';
    const CACHE_LDAP_GROUP = 'permissions:ldap.group.%s';
    const CACHE_LDAP_USER = 'permissions:ldap.user.%s';

    //// NEW GROUPS (clarified membership)

    /**
     * Key Masters Group
     *
     * - Can see the admin page.
     * - Can read, write, update, and delete all entities.
     * - Can build and push all repositories to all environments.
     */
    const DN_KEYMASTER      = 'CN=git-admin-prod,OU=GIT,DC=mi,DC=corp';

    /**
     * Super Admin Group (Web Core)
     *
     * - Can see the admin page.
     * - Can see the super admin page.
     * - Can read, write, update, and delete all entities.
     * - Can build and push all repositories to all non-production environments.
     */
    const DN_SUPERADMIN     = 'CN=IT Team Web Core,OU=GIT,DC=mi,DC=corp';

    /**
     * Admin Group
     *
     * - Can read and update entities for all repositories they have been granted access to.
     */
    const DN_ADMIN          = 'CN=git-admin,OU=GIT,DC=mi,DC=corp';

    //// OLD GROUPS (to be removed)

    /**
     * Super Admin Group
     *
     * Can read, write, update, and delete all entities. Can build and push all repositories in all environments. Can
     * see the full HAL 9000 UI.
     */
    const DN_ADMIN_SUPER    = 'CN=git-admin-prod,OU=GIT,DC=mi,DC=corp';

    /**
     * HAL Admin Group
     *
     * Can read, write, update, and delete all entities. Can build and push all repositories in all non-production
     * environments. Can build and push the HAL 9000 repository in all environments. Can see the full HAL 9000 UI.
     */
    const DN_ADMIN_HAL      = 'CN=git-hal,OU=GIT,DC=mi,DC=corp';

    /**
     * Project Admins Group
     *
     * Can read, write, and update all entities. Can see the full HAL 9000 UI.
     */
    const DN_ADMIN_PROJECT  = 'CN=git-admin,OU=GIT,DC=mi,DC=corp';

    //// END OLD GROUPS

    /**
     * Build & Push Permissions Group
     *
     * Can build and push the specified repository in the specified environment.
     */
    const DN_REPOSITORY     = 'CN=git-%s-%s,OU=GIT,DC=mi,DC=corp';

    /**
     * @var LdapService
     */
    private $ldap;

    /**
     * @var DeploymentRepository
     */
    private $deployments;

    /**
     * @var RepositoryRepository
     */
    private $repositories;

    /**
     * @var UserRepository
     */
    private $users;

    /**
     * @var EnvironmentRepository
     */
    private $environments;

    /**
     * @var GithubService
     */
    private $github;

    /**
     * The common ID of a user that will be granted Super Admin status. Used for testing only.
     *
     * @var string
     */
    private $god;

    /**
     * @var array
     */
    private $productionEnvironments;

    /**
     * @var array
     */
    private $halRepos;

    /**
     * @param LdapService $ldap
     * @param DeploymentRepository $deployments
     * @param RepositoryRepository $repositories
     * @param UserRepository $users
     * @param EnvironmentRepository $environments
     * @param GithubService $github
     * @param string $god
     */
    public function __construct(
        LdapService $ldap,
        DeploymentRepository $deployments,
        RepositoryRepository $repositories,
        UserRepository $users,
        EnvironmentRepository $environments,
        GithubService $github,
        $god
    ) {
        $this->ldap = $ldap;
        $this->deployments = $deployments;
        $this->repositories = $repositories;
        $this->users = $users;
        $this->environments = $environments;
        $this->github = $github;
        $this->god = $god;

        $this->productionEnvironments = [
            'prod',
            'production'
        ];

        $this->halRepos = [
            'hal9000',
            'hal9000-agent'
        ];
    }

    ####################################################################################################################
    # APPLICATION RULES
    ####################################################################################################################

    /**
     * Check if a user is allowed to perform super admin functions
     *
     * @param LdapUser|string $user
     * @return bool
     */
    public function allowSuperAdmin($user)
    {
        $user = $this->getUser($user);

        if (!($user instanceof LdapUser)) {
            // user not found in ldap
            return false;
        }

        // Super Admin
        if ($this->isUserInGroup($user, $this->generateSuperAdminDn())) {
            return true;
        }

        // God Override
        if ($user->commonId() == $this->god) {
            return true;
        }

        return false;
    }

    /**
     * Check if user is allowed to view admin pages
     *
     * Super Admins, HAL Admins, and Project Admins are allowed to view these pages.
     *
     * @param LdapUser|string $user
     * @return bool
     */
    public function allowAdmin($user)
    {
        $user = $this->getUser($user);

        if (!($user instanceof LdapUser)) {
            // user not found in ldap
            return false;
        }

        // Super Admin
        if ($this->isUserInGroup($user, $this->generateSuperAdminDn())) {
            return true;
        }

        // HAL Admin
        if ($this->isUserInGroup($user, $this->generateHalAdminDn())) {
            return true;
        }

        // Project Admin
        if ($this->isUserInGroup($user, $this->generateProjectAdminDn())) {
            return true;
        }

        // God Override
        if ($user->commonId() == $this->god) {
            return true;
        }

        return false;
    }

    /**
     * Check if a user is allowed to delete entities
     *
     * Super Admins and HAL Admins are allowed to do this.
     *
     * @param LdapUser|string $user
     * @return bool
     */
    public function allowDelete($user)
    {
        $user = $this->getUser($user);

        if (!($user instanceof LdapUser)) {
            // user not found in ldap
            return false;
        }

        // Super Admin
        if ($this->isUserInGroup($user, $this->generateSuperAdminDn())) {
            return true;
        }

        // HAL Admin
        if ($this->isUserInGroup($user, $this->generateHalAdminDn())) {
            return true;
        }

        // God Override
        return $this->allowGodMode($user);
    }

    /**
     * Check if a user is allowed god mode access
     *
     * @param $user
     * @return bool
     */
    public function allowGodMode($user)
    {
        $user = $this->getUser($user);

        if (!($user instanceof LdapUser)) {
            // user not found in ldap
            return false;
        }

        if ($user->commonId() == $this->god) {
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
        return ($this->allowPushByMatchingRule($user, $repository, $environment) === false) ? false : true;
    }

    /**
     * Check if a user is allowed to build a given repository
     *
     * You can build if you're an admin or have any connection to the repository (collaborator or any LDAP permission)
     *
     * @param $user
     * @param $repository
     * @return bool
     */
    public function allowBuild($user, $repository)
    {
        return ($this->allowBuildByMatchingRule($user, $repository) === false) ? false : true;
    }

    /**
     * Check if a user is allowed to push to a given repository:environment pair and if the result is true, return
     * the matching rule that allowed access. Return false otherwise.
     *
     * @param $user
     * @param $repository
     * @param $environment
     * @return string|false
     */
    public function allowPushByMatchingRule($user, $repository, $environment)
    {
        $user = $this->getUser($user);

        if (!($user instanceof LdapUser)) {
            // user not found in ldap
            return false;
        }

        // Super Admin
        if ($this->isUserInGroup($user, $this->generateSuperAdminDn())) {
            return 'ldap:super-admin';
        }

        // HAL Admin (HAL 9000 Push Permission)
        if (in_array($repository, $this->halRepos) && $this->isUserInGroup($user, $this->generateHalAdminDn())) {
            return 'ldap:hal-admin';
        }

        // Non-Production Rules
        if (!$this->isEnvironmentProduction($environment)) {

            // HAL Admin
            if ($this->isUserInGroup($user, $this->generateHalAdminDn())) {
                return 'ldap:hal-admin (non-prod)';
            }

            // Github Collaborators
            if ($this->isUserCollaborator($user, $repository)) {
                return 'github:collaborator (non-prod)';
            }

            // LDAP Repository Permissions
            if ($this->isUserInGroup($user, $this->generateRepositoryDn($repository, $environment))) {
                return 'ldap:repository-group (non-prod)';
            }
        }

        // God Override
        return $this->allowGodMode($user);
    }

    /**
     * Check if a user is allowed to build a given repository and, if the result is true, return the matching tool
     * that allowed access.
     *
     * You can build if you're an admin or have any connection to the repository (collaborator or any LDAP permission)
     *
     * @param $user
     * @param $repository
     * @return bool
     */
    public function allowBuildByMatchingRule($user, $repository)
    {
        $user = $this->getUser($user);

        if (!($user instanceof LdapUser)) {
            // user not found in ldap
            return false;
        }

        // Super Admin
        if ($this->isUserInGroup($user, $this->generateSuperAdminDn())) {
            return 'ldap:super-admin';
        }

        // HAL Admin (HAL 9000 Push Permission)
        if (in_array($repository, $this->halRepos) && $this->isUserInGroup($user, $this->generateHalAdminDn())) {
            return 'ldap:hal-admin';
        }

        // Project Admin
        if ($this->isUserInGroup($user, $this->generateProjectAdminDn())) {
            return 'ldap:project-admin';
        }

        // Github Collaborators
        if ($this->isUserCollaborator($user, $repository)) {
            return 'github:collaborator';
        }

        // Any user that can push to any environment
        foreach ($this->environments->findAll() as $environment) {
            if ($rule = $this->allowPushByMatchingRule($user, $repository, $environment->getKey())) {
                return sprintf('push::%s', $rule);
            }
        }

        // God Override
        return $this->allowGodMode($user);
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
        $user = $this->getUser($user);
        if (!$user instanceof LdapUser) {
            // user not found in ldap
            return true;
        }

        // don't show for HAL admins (web-core folks)
        if ($this->isUserInGroup($user, $this->generateHalAdminDn())) {
            return false;
        }

        return true;
    }

    ####################################################################################################################
    # PERMISSION PAIR QUERIES
    ####################################################################################################################

    /**
     * Get a list of repositories a user has access to (can build for)
     *
     * @param $user
     * @return array
     */
    public function userRepositories($user)
    {
        $user = $this->getUser($user);

        if (!$user instanceof LdapUser) {
            // user not found in ldap
            return [];
        }

        $repositories = [];

        foreach ($this->repositories->findBy([], ['key' => 'ASC']) as $repo) {
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
        $user = $this->getUser($user);

        if (!($user instanceof LdapUser)) {
            // user not found in ldap
            return [];
        }

        $permissions = [];

        foreach ($this->getPermissionPairs() as $pair) {
            if ($rule = $this->allowPushByMatchingRule($user, $pair['repository']->getKey(), $pair['environment']->getKey())) {
                $pair['rule'] = $rule;
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
            if ($rule = $this->allowBuildByMatchingRule($user, $pair['repository']->getKey(), $pair['environment']->getKey())) {
                $pair['rule'] = $rule;
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
        $repository = $this->repositories->findOneBy(['key' => $repository]);
        $users = $this->users->findBy(['isActive' => true], ['name' => 'ASC']);

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
    # PERMISSION PAIR HELPERS
    ####################################################################################################################

    /**
     * @param Repository $repository
     * @return array
     */
    private function getPermissionPairs(Repository $repository = null)
    {
        if ($repository) {
            $deployments = $this->deployments->findBy(['repository' => $repository]);
        } else {
            $deployments = $this->deployments->findAll();
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

    ####################################################################################################################
    # ENVIRONMENT HELPERS
    ####################################################################################################################

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

        $repository = $this->repositories->findOneBy(['key' => $repository]);

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
     * Check if a user is in an LDAP group
     *
     * @param LdapUser $user
     * @param Dn $group
     * @return bool
     */
    private function isUserInGroup(LdapUser $user, Dn $group)
    {
        $users = $this->usersInGroup($group);

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
     * Get an LDAP user by Windows Username
     *
     * @param string|LdapUser|EntityUser $user
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

        if ($user instanceof EntityUser) {
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

    ####################################################################################################################
    # LDAP HELPERS
    ####################################################################################################################

    /**
     * Generate an LDAP DN for the Super Admins group.
     *
     * @return Dn
     */
    private function generateSuperAdminDn()
    {
        return $this->generateDn(self::DN_ADMIN_SUPER);
    }

    /**
     * Generate an LDAP DN for the HAL Admins group.
     *
     * @return Dn
     */
    private function generateHalAdminDn()
    {
        return $this->generateDn(self::DN_ADMIN_HAL);
    }

    /**
     * Generate an LDAP DN for the Project Admins group.
     *
     * @return Dn
     */
    private function generateProjectAdminDn()
    {
        return $this->generateDn(self::DN_ADMIN_PROJECT);
    }

    /**
     * Generate an LDAP DN for repository build and push permissions.
     *
     * @param string $repository
     * @param string $environment
     * @return Dn
     */
    private function generateRepositoryDn($repository, $environment)
    {
        return $this->generateDn(
            sprintf(
                self::DN_REPOSITORY,
                $repository,
                $environment
            )
        );
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
