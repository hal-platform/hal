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

/**
 * @api
 */
class PushPermissionService
{
    const PERM_DN_TPL = 'CN=git-%s-%s,OU=GIT,DC=mi,DC=corp';
    const PERM_DN_ADMIN = 'CN=git-admin,OU=GIT,DC=mi,DC=corp';

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
     * @param LdapService $ldapService
     * @param RepositoryService $repoService
     * @param int $godModeOverride
     */
    public function __construct(
        LdapService $ldapService,
        RepositoryService $repoService,
        $godModeOverride
    ) {
        $this->ldapService = $ldapService;
        $this->repoService = $repoService;
        $this->godModeOverride = $godModeOverride;
        $this->authed = false;
    }

    /**
     * @param User $user
     * @param string $repoShortName
     * @param string $envShortName
     * @return bool
     */
    public function canUserPushToEnvRepo(User $user, $repoShortName, $envShortName)
    {
        $this->checkAuthenticated();

        if ($this->isUserAdmin($user)) {
            return true;
        }

        $group = self::getDnForPermGroup($repoShortName, $envShortName);

        return $this->ldapService->isUserInGroup($group, $user->dn());
    }

    /**
     * @param string $commonId
     * @return array
     */
    public function repoEnvsCommonIdCanPushTo($commonId)
    {
        $this->checkAuthenticated();
        $user = $this->ldapService->getUserByCommonId($commonId);

        $pairs = $this->repoService->listRepoEnvPairs();

        if ($this->isUserAdmin($user)) {
            return array_map(function ($v) { return array($v['RepShortName'], $v['EnvShortName']); }, $pairs);
        }

        $return = array();
        foreach ($pairs as $pair) {
            $permGroup = self::getDnForPermGroup($pair['RepShortName'], $pair['EnvShortName']);
            if ($this->ldapService->isUserInGroup($permGroup, $user->dn())) {
                $return[] = array($pair['RepShortName'], $pair['EnvShortName']);
            }
        }

        return $return;
    }

    public function checkAuthenticated()
    {
        if ($this->authed) {
            return;
        }
        $this->ldapService->authenticate('placeholder', 'placeholder', false);
    }

    public function isUserAdmin(User $user)
    {
        $this->checkAuthenticated();
        if ($user->commonId() == $this->godModeOverride) {
            return true;
        }
        return $this->ldapService->isUserInGroup(self::dnForAdminGroup(), $user->dn());
    }
}
