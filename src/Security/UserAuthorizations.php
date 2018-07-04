<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Security;

use Hal\Core\Entity\Application;
use Hal\Core\Entity\Environment;
use Hal\Core\Entity\Organization;
use Hal\Core\Type\UserPermissionEnum;
use JsonSerializable;

/**
 * Easily cacheable convenience container for user permissions and authorizations.
 */
class UserAuthorizations implements JsonSerializable
{
    public const ALL_PERMISSIONS = 'all';

    /**
     * @var array
     */
    private $tiers;

    /**
     * @param array $tiers
     */
    public function __construct(array $tiers)
    {
        $this->tiers = $tiers;
    }

    /**
     * @param Application|Organization $of
     *
     * @return bool
     */
    public function isMemberOf($of): bool
    {
        if ($of instanceof Application) {
            $hash = $this->simpleHash($of);
            return $this->hasEntry(UserPermissionEnum::TYPE_MEMBER, $hash);

        } elseif ($of instanceof Organization) {
            $hash = $this->simpleHash($of);
            return $this->hasEntry(UserPermissionEnum::TYPE_MEMBER, $hash);
        }

        return false;
    }

    /**
     * @param Application|Organization|Environment $of
     *
     * @return bool
     */
    public function isOwnerOf($of): bool
    {
        if ($of instanceof Application) {
            $hash = $this->simpleHash($of);
            return $this->hasEntry(UserPermissionEnum::TYPE_OWNER, $hash);

        } elseif ($of instanceof Organization) {
            $hash = $this->simpleHash($of);
            return $this->hasEntry(UserPermissionEnum::TYPE_OWNER, $hash);

        } elseif ($of instanceof Environment) {
            $hash = $this->simpleHash($of);
            return $this->hasEntry(UserPermissionEnum::TYPE_OWNER, $hash);
        }

        return false;
    }

    /**
     * @param Environment $of
     *
     * @return bool
     */
    public function isAdminOf($of): bool
    {
        if ($of instanceof Environment) {
            $hash = $this->simpleHash($of);
            return $this->hasEntry(UserPermissionEnum::TYPE_ADMIN, $hash);
        }

        return false;
    }

    /**
     * @return bool
     */
    public function isMember(): bool
    {
        return count($this->tiers[UserPermissionEnum::TYPE_MEMBER]) > 0;
    }

    /**
     * @return bool
     */
    public function isOwner(): bool
    {
        return count($this->tiers[UserPermissionEnum::TYPE_OWNER]) > 0;
    }

    /**
     * @return bool
     */
    public function isAdmin(): bool
    {
        return count($this->tiers[UserPermissionEnum::TYPE_ADMIN]) > 0;
    }

    /**
     * @return bool
     */
    public function isSuper(): bool
    {
        return count($this->tiers[UserPermissionEnum::TYPE_SUPER]) > 0;
    }

    /**
     * @return array
     */
    public function getTiers(): array
    {
        $tiers = [];

        if ($this->isMember()) {
            $tiers[] = UserPermissionEnum::TYPE_MEMBER;
        }

        if ($this->isOwner()) {
            $tiers[] = UserPermissionEnum::TYPE_OWNER;
        }

        if ($this->isAdmin()) {
            $tiers[] = UserPermissionEnum::TYPE_ADMIN;
        }

        if ($this->isSuper()) {
            $tiers[] = UserPermissionEnum::TYPE_SUPER;
        }


        return $tiers;
    }

    /**
     * @param Application $application
     *
     * @return bool
     */
    public function canBuild($application): bool
    {
        if (!$application instanceof Application) {
            return false;
        }

        $organization = $application->organization();

        if ($this->isMemberOf($application) || $this->isOwnerOf($application)) {
            return true;
        } elseif ($this->isMemberOf($organization) || $this->isOwnerOf($organization)) {
            return true;
        }

        //admin is only for environments?
//        if ($this->isAdminOf()) {
//            return true;
//        }

        if ($this->isSuper()) {
            return true;
        }

        return false;
    }

    /**
     * @param Application $application
     * @param Environment $environment
     *
     * @return bool
     */
    public function canDeploy($application, $environment): bool
    {
        if (!$application instanceof Application) {
            return false;
        }

        if (!$environment instanceof Environment) {
            return false;
        }

        if ($this->canBuild($application) && !$environment->isProduction()) {
            return true;
        }

        if ($this->isAdminOf($environment)) {
            return true;
        }

        if ($this->isSuper()) {
            return true;
        }

        return false;
    }

    /**
     * @param string $authorizationType
     *
     * @return array
     */
    public function getAuthorizationsFor($authorizationType): array
    {
        return $this->tiers[$authorizationType] ?? [];
    }

    /**
     * @param string $tier
     * @param string $hash
     *
     * @return bool
     */
    private function hasEntry($tier, $hash)
    {
        return isset($this->tiers[$tier][$hash]);
    }

    /**
     * @param Application|Organization|Environment|null $of
     *
     * @return string
     */
    private function simpleHash($of)
    {
        if ($of instanceof Application) {
            return self::hash($of, null, null);

        } elseif ($of instanceof Organization) {
            return self::hash(null, $of, null);

        } elseif ($of instanceof Environment) {
            return self::hash(null, null, $of);
        }

        return self::hash(null, null, null);
    }

    /**
     * @param Application|null $application
     * @param Organization|null $organization
     * @param Environment|null $environment
     *
     * @return string
     */
    public static function hash(?Application $application, ?Organization $organization, ?Environment $environment)
    {
        $application = $application ? $application->id() : UserAuthorizations::ALL_PERMISSIONS;
        $organization = $organization ? $organization->id() : UserAuthorizations::ALL_PERMISSIONS;
        $environment = $environment ? $environment->id() : UserAuthorizations::ALL_PERMISSIONS;

        return md5($application . $organization . $environment);
    }

    /**
     * @param array $data
     *
     * @return self
     */
    public static function fromSerialized(array $data)
    {
        return new self($data['tiers'] ?? []);
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        $json = [
            'tiers' => $this->tiers,
        ];

        return $json;
    }
}
