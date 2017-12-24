<?php
/**
 * @copyright (c) 2017 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Validator;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Hal\Core\Entity\Application;
use Hal\Core\Entity\Organization;
use Hal\Core\Entity\User;
use Hal\Core\Entity\UserPermission;
use Hal\Core\Type\UserPermissionEnum;
use Hal\UI\Security\UserAuthorizations;

class PermissionsValidator
{
    use NewValidatorTrait;
    use ValidatorErrorTrait;

    private const ERR_INVALID_TYPE = 'Please select a valid permission type.';
    private const ERR_CANNOT_ADD_SUPER = 'You are not allowed to add super permissions.';
    private const ERR_CANNOT_ADD_ADMIN = 'You are not allowed to add admin permissions.';
    private const ERR_DUPLICATE_PERMISSION = 'Cannot add permissions. This user already has this permission.';
    private const ERR_SCOPE_REQUIRED = 'Please select an Application or Organization.';
    private const ERR_APPLICATION_NOT_FOUND = 'Application not found.';
    private const ERR_ORGANIZATION_NOT_FOUND = 'Organization not found.';
    private const ERR_APPLICATION_AND_ORGANIZATION = 'Please select only an organization or application.';

    private const ERR_CANNOT_REMOVE_SUPER = 'You are not allowed to remove super admins.';
    private const ERR_LAST_ADMIN = 'There must be at least one admin left.';
    private const ERR_LAST_SUPER = 'There must be at least one super admin left.';

    /**
     * @var EntityRepository
     */
    private $applicationRepository;
    private $organizationRepository;

    /**
     * @param EntityManagerInterface $em
     */
    public function __construct(EntityManagerInterface $em)
    {
        $this->applicationRepository = $em->getRepository(Application::class);
        $this->organizationRepository = $em->getRepository(Organization::class);
    }

    /**
     * @param string $type
     * @param UserAuthorizations $userAuthorizations
     * @param UserAuthorizations $selectedAuthorizations
     * @param User $selectedUser
     * @param null $applicationID
     * @param null $organizationID
     *
     * @return UserPermission|null
     */
    public function isValid(
        string $type,
        UserAuthorizations $userAuthorizations,
        UserAuthorizations $selectedAuthorizations,
        User $selectedUser,
        $applicationID = null,
        $organizationID = null
    ): ?UserPermission {
        if (!in_array($type, UserPermissionEnum::options())) {
            $this->addError(self::ERR_INVALID_TYPE);
        }

        // Only supers can add supers
        if ($type === UserPermissionEnum::TYPE_SUPER && !$userAuthorizations->isSuper()) {
            $this->addError(self::ERR_CANNOT_ADD_SUPER);
        }

        // Only (admins, supers) can add admins
        if ($type === UserPermissionEnum::TYPE_ADMIN && !($userAuthorizations->isAdmin() || $userAuthorizations->isSuper())) {
            $this->errors[] = self::ERR_CANNOT_ADD_ADMIN;
        }

        if ($type === UserPermissionEnum::TYPE_SUPER && $selectedAuthorizations->isSuper()) {
            $this->addError(self::ERR_DUPLICATE_PERMISSION);
        }

        if ($applicationID && $organizationID) {
            $this->addError(self::ERR_APPLICATION_AND_ORGANIZATION);
        }

        // If we're making an owner and no appId is passed
        $scoped = [UserPermissionEnum::TYPE_MEMBER, UserPermissionEnum::TYPE_OWNER];

        if (in_array($type, $scoped, true) && (!$applicationID && !$organizationID)) {
            $this->errors[] = self::ERR_SCOPE_REQUIRED;
        }

        if ($this->hasErrors()) {
            return null;
        }

        $organization = $application = null;

        if (in_array($type, $scoped, true) && $applicationID) {
            if (!$application = $this->applicationRepository->find($applicationID)) {
                $this->addError(self::ERR_APPLICATION_NOT_FOUND);
            }
        }

        if (in_array($type, $scoped, true) && $organizationID) {
            if (!$organization = $this->organizationRepository->find($organizationID)) {
                $this->addError(self::ERR_ORGANIZATION_NOT_FOUND);
            }
        }

        if ($this->hasErrors()) {
            return null;
        }

        // User is already a member
        if ($type === UserPermissionEnum::TYPE_MEMBER &&
            ($selectedAuthorizations->isMemberOf($application) || $selectedAuthorizations->isMemberOf($organization))
        ) {
            $this->addError(self::ERR_DUPLICATE_PERMISSION);
        }

        // User is already an owner
        if (
            $type === UserPermissionEnum::TYPE_OWNER &&
            ($selectedAuthorizations->isOwnerOf($application) || $selectedAuthorizations->isOwnerOf($organization))
        ) {
            $this->addError(self::ERR_DUPLICATE_PERMISSION);
        }

        if ($this->hasErrors()) {
            return null;
        }

        $permissions = (new UserPermission)
            ->withType($type)
            ->withUser($selectedUser)
            ->withApplication($application)
            ->withOrganization($organization);

        return $permissions;
    }
}
