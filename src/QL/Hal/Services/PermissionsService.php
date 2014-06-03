<?php

namespace QL\Hal\Service;

use MCP\Corp\Account\LdapService;
use MCP\Corp\Account\User;
use MCP\Cache\CacheInterface;
use QL\Hal\Core\Entity\Repository\RepositoryRepository;
use QL\Hal\Core\Entity\Repository\UserRepository;
use QL\Hal\Core\Entity\Repository\DeploymentRepository;
use QL\Hal\Core\Entity\Repository;
use QL\Hal\Core\Entity\Deployment;
use QL\Hal\Services\GithubService;
use Zend\Ldap\Dn;

/**
 * Permissions Service
 *
 * Determines user visibility, build, and push permissions for HAL 9000.
 *
 * @author Matt Colf <matthewcolf@quickenloans.com>
 */
class PermissionService
{
    /**
     * Super Admin Group (keymasters)
     *
     * Can read, write, update, and delete all entities. Can build and push all repositories in all environments. Can
     * see the full HAL 9000 UI.
     */
    const DN_ADMIN_SUPER    = 'CN=git-admin-prod,OU=GIT,DC=mi,DC=corp';

    /**
     * HAL Admin Group (web core)
     *
     * Can read, write, update, and delete all entities. Can build and push all repositories in all non-production
     * environments. Can build and push the HAL 9000 repository in all environments. Can see the full HAL 9000 UI.
     */
    const DN_ADMIN_HAL      = 'CN=Web Core,OU=GIT,DC=mi,DC=corp';

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

    private $productionEnvironments;

    public function __construct(
        LdapService $ldap,
        DeploymentRepository $deployments,
        RepositoryRepository $repositories,
        UserRepository $users,
        GithubService $github,
        CacheInterface $cache,
        $god
    ) {
        $this->ldap = $ldap;
        $this->deployments = $deployments;
        $this->repositories = $repositories;
        $this->users = $users;
        $this->github = $github;
        $this->cache = $cache;
        $this->god = $god;

        $this->productionEnvironments = [
            'prod',
            'production'
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
     * @param User|string $user
     * @return bool
     */
    public function allowAdmin($user)
    {
        if (!($user instanceof User)) {
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
     * @param User|string $user
     * @return bool
     */
    public function allowDelete($user)
    {
        if (!($user instanceof User)) {
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
     * @param User|string $user
     * @param string $repository
     * @param string $environment
     * @return bool
     */
    public function allowPush($user, $repository, $environment)
    {
        if (!($user instanceof User)) {
            $user = $this->getUser($user);
        }

        // Super Admin
        if ($this->isUserInGroup($user, $this->generateSuperAdminDn())) {
            return true;
        }

        // Non-Production Rules
        if (!$this->isEnvironmentProduction($environment)) {

            // HAL Admin
            if ($repository == 'hal9000' && $this->isUserInGroup($user, $this->generateHalAdminDn())) {
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

    ####################################################################################################################
    # PERMISSION PAIR QUERIES
    ####################################################################################################################

    /**
     * Get all permission pairs for a user
     *
     * @param User $user
     * @return array
     */
    public function userPermissionPairs(User $user)
    {
        $permissions = [];

        foreach ($this->getPermissionPairs() as $pair) {
            if ($this->allowPush($user, $pair['repository'], $pair['environment'])) {
                $permissions[] = $pair;
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

        $permissions = [];

        foreach ($this->getPermissionPairs($repository) as $pair) {
            foreach ($this->users->findAll() as $user) {
                if ($this->allowPush($user, $pair['repository'], $pair['environment'])) {
                    $permissions[] = [
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
            $deployments = $this->deployments->findBy(['key' => $repository]);
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
     * @param User $user
     * @param $repository
     * @return bool
     */
    private function isUserCollaborator(User $user, $repository)
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
     * @param User $user
     * @param Dn $group
     * @return bool
     */
    private function isUserInGroup(User $user, Dn $group)
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
     * @return User[]
     */
    private function usersInGroup(Dn $group)
    {
        $key = 'ldap.group.'.md5($group);

        if ($users = $this->cache->get($key)) {
            return $users;
        }

        $users = $this->ldap->usersInGroup($group);
        $this->cache->set($key, $users);

        return $users;
    }

    /**
     * Get an LDAP user by Windows Username
     *
     * @param $username
     * @return User|null
     */
    private function getUser($username)
    {
        $key = 'ldap.user.'.md5($username);

        if ($user = $this->cache->get($key)) {
            return $user;
        }

        $user = $this->ldap->getUserByWindowsUsername($username);
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
