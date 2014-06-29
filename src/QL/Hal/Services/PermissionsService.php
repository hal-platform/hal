<?php

namespace QL\Hal\Services;

use MCP\Corp\Account\LdapService;
use MCP\Cache\CacheInterface;
use QL\Hal\Core\Entity\Repository\RepositoryRepository;
use QL\Hal\Core\Entity\Repository\UserRepository;
use QL\Hal\Core\Entity\Repository\DeploymentRepository;
use QL\Hal\Core\Entity\Repository\EnvironmentRepository;
use QL\Hal\Core\Entity\Repository;
use QL\Hal\Core\Entity\Deployment;
use MCP\Corp\Account\User as LdapUser;
use QL\Hal\Core\Entity\User as EntityUser;
use Zend\Ldap\Dn;

/**
 * Permissions Service
 *
 * Determines user visibility, build, and push permissions for HAL 9000.
 *
 * @author Matt Colf <matthewcolf@quickenloans.com>
 */
class PermissionsService
{
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
     * @var CacheInterface
     */
    private $cache;

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
     * @param CacheInterface $cache
     * @param string $god
     */
    public function __construct(
        LdapService $ldap,
        DeploymentRepository $deployments,
        RepositoryRepository $repositories,
        UserRepository $users,
        EnvironmentRepository $environments,
        GithubService $github,
        CacheInterface $cache,
        $god
    ) {
        $this->ldap = $ldap;
        $this->deployments = $deployments;
        $this->repositories = $repositories;
        $this->users = $users;
        $this->environments = $environments;
        $this->github = $github;
        $this->cache = $cache;
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
     * Check if user is allowed to view admin pages
     *
     * Super Admins, HAL Admins, and Project Admins are allowed to view these pages.
     *
     * @param LdapUser|string $user
     * @return bool
     */
    public function allowAdmin($user)
    {
        if (!($user instanceof LdapUser)) {
            $user = $this->getUser($user);
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
        if (!($user instanceof LdapUser)) {
            $user = $this->getUser($user);
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
        if (!($user instanceof LdapUser)) {
            $user = $this->getUser($user);
        }

        // Super Admin
        if ($this->isUserInGroup($user, $this->generateSuperAdminDn())) {
            return true;
        }

        // HAL Admin (HAL 9000 Push Permission)
        if (in_array($repository, $this->halRepos) && $this->isUserInGroup($user, $this->generateHalAdminDn())) {
            return true;
        }

        // Non-Production Rules
        if (!$this->isEnvironmentProduction($environment)) {

            // HAL Admin
            if ($this->isUserInGroup($user, $this->generateHalAdminDn())) {
                return true;
            }

            // Github Collaborators
            if ($this->isUserCollaborator($user, $repository)) {
                return true;
            }

            // LDAP Repository Permissions
            if ($this->isUserInGroup($user, $this->generateRepositoryDn($repository, $environment))) {
                return true;
            }
        }

        // God Override
        if ($user->commonId() == $this->god) {
            return true;
        }

        return false;
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
        if (!($user instanceof LdapUser)) {
            $user = $this->getUser($user);
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

        // Github Collaborators
        if ($this->isUserCollaborator($user, $repository)) {
            return true;
        }

        // LDAP Repository Permissions (any environment)
        foreach ($this->environments->findAll() as $environment) {
            if ($this->isUserInGroup($user, $this->generateRepositoryDn($repository, $environment->getKey()))) {
                return true;
            }
        }

        // God Override
        if ($user->commonId() == $this->god) {
            return true;
        }

        return false;
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
        if (!($user instanceof LdapUser)) {
            $user = $this->getUser($user);
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
     * Get all permission pairs for a user
     *
     * @param LdapUser|string $user
     * @return array
     */
    public function userPermissionPairs($user)
    {
        if (!($user instanceof LdapUser)) {
            $user = $this->getUser($user);
        }

        $permissions = [];

        foreach ($this->getPermissionPairs() as $pair) {
            if ($this->allowPush($user, $pair['repository']->getKey(), $pair['environment']->getKey())) {
                //$permissions[] = $pair;
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
        $users = $this->users->findBy([], ['name' => 'ASC']);

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
        $key = 'github.collaborator.'.md5($user->commonId().$repository);

        if ($member = $this->cache->get($key)) {
            return $member;
        }

        $repository = $this->repositories->findOneBy(['key' => $repository]);

        $member = $this->github->isUserCollaborator(
            $repository->getGithubUser(),
            $repository->getGithubRepo(),
            $user->windowsUsername()
        );
        $this->cache->set($key, $member);

        return $member;
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
        $key = 'ldap.group.'.md5($group);

        if ($result = $this->cache->get($key)) {
            return $result;
        }

        $users = $this->ldap->usersInGroup($group);
        $this->cache->set($key, $users);

        return $users;
    }

    /**
     * Get an LDAP user by Windows Username
     *
     * @param $user
     * @return LdapUser|null
     */
    private function getUser($user)
    {
        if ($user instanceof LdapUser) {
            return $user;
        }
        if ($user instanceof EntityUser) {
            $user = $user->getHandle();
        }

        $key = 'ldap.user.'.md5($user);

        if ($result = $this->cache->get($key)) {
            return $result;
        }

        $user = $this->ldap->getUserByWindowsUsername($user);
        $this->cache->set($key, $user);

        return $user;
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
