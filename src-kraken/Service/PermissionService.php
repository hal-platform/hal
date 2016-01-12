<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Kraken\Service;

use QL\Hal\Core\Entity\User;
use QL\Hal\Service\PermissionService as HalPermissionService;
use QL\Kraken\Core\Entity\Application;
use QL\Kraken\Core\Entity\Environment;

class PermissionService
{
    const CACHE_CAN_DEPLOY = 'permissions:kraken.deploy.%s.%s.%s';

    /**
     * @var HalPermissionService
     */
    private $permission;

    /**
     * Simple in-memory cache
     *
     * @var array
     */
    private $internalCache;

    private $superApplications;

    /**
     * @param HalPermissionService $permissions
     */
    public function __construct(
        HalPermissionService $permissions
    ) {
        $this->permissions = $permissions;

        $this->internalCache = [];
        $this->superApplications = [
            '200947',
            '201111'
        ];
    }


    /**
     * @param User $user
     *
     * @return UserPerm
     */
    public function getUserPermissions(User $user)
    {
        return $this->permissions->getUserPermissions($user);
    }

    /**
     * Can the user change or deploy the configuration for this environment?
     *
     * @param User $user
     * @param Application $application
     * @param Environment $environment
     *
     * @return bool
     */
    public function canUserDeploy(User $user, Application $application, Environment $environment)
    {
        $key = sprintf(self::CACHE_CAN_DEPLOY, $user->id(), $application->id(), $environment->id());

        // internal cache
        if (null !== ($cached = $this->getFromInternalCache($key))) {
            return $cached;
        }

        $perm = $this->permissions->getUserPermissions($user);

        if ($perm->isButtonPusher()) {
            return $this->setToInternalCache($key, true);
        }

        if ($perm->isSuper()) {
            // Super can change super apps or non-prods
            if ($this->isSuperApplication($application) || !$environment->isProduction()) {
                return $this->setToInternalCache($key, true);
            }
        }

        // lead and deployment permissions are based on hal applications.
        if ($halApplication = $application->halApplication()) {

            if ($environment->isProduction()) {
                if ($perm->canDeployApplicationToProd($halApplication)) {
                    return $this->setToInternalCache($key, true);
                }

            } else {

                // Non-prod fallback to building permissions, which includes github collabs
                if ($this->permissions->canUserBuild($user, $halApplication)) {
                    return $this->setToInternalCache($key, true);
                }
            }
        }

        return $this->setToInternalCache($key, false);
    }

    /**
     * @param Application $application
     *
     * @return bool
     */
    private function isSuperApplication(Application $application)
    {
        return in_array($application->coreId(), $this->superApplications);
    }

    /**
     * @param string $key
     *
     * @return mixed
     */
    private function getFromInternalCache($key)
    {
        if (array_key_exists($key, $this->internalCache)) {
            return $this->internalCache[$key];
        }

        return null;
    }

    /**
     * @param string $key
     * @param mixed $value
     *
     * @return mixed
     */
    private function setToInternalCache($key, $value)
    {
        return $this->internalCache[$key] = $value;
    }
}
