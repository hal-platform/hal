<?php
/**
 * @copyright (c) 2017 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Validator;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Hal\Core\Entity\Organization;

class OrganizationValidator
{
    use ValidatorErrorTrait;
    use ValidatorTrait;

    private const REGEX_CHARACTER_WHITESPACE = '\f\n\r\t\v';
    private const ERR_NAME_CHARACTERS = 'Name must not contain tabs or newlines';
    private const ERR_DUPE_NAME = 'An organization with this name already exists';

    /**
     * @var EntityRepository
     */
    private $organizationRepo;

    /**
     * @param EntityManagerInterface $em
     */
    public function __construct(EntityManagerInterface $em)
    {
        $this->organizationRepo = $em->getRepository(Organization::class);
    }

    /**
     * @param string $name
     *
     * @return Organization|null
     */
    public function isValid($name): ?Organization
    {
        $this->resetErrors();

        $this->validate($name);

        if ($this->hasErrors()) {
            return null;
        }

        if ($org = $this->organizationRepo->findOneBy(['name' => $name])) {
            $this->addError(self::ERR_DUPE_NAME);
        }

        if ($this->hasErrors()) {
            return null;
        }

        $organization = (new Organization)
            ->withName($name);

        return $organization;
    }

    /**
     * @param Organization $organization
     * @param string $name
     *
     * @return Organization|null
     */
    public function isEditValid(Organization $organization, $name): ?Organization
    {
        $this->resetErrors();

        $this->validate($name);

        if ($this->hasErrors()) {
            return null;
        }

        if ($organization->name() !== $name) {
            if ($org = $this->organizationRepo->findOneBy(['name' => $name])) {
                $this->addError(self::ERR_DUPE_NAME);
            }

            if ($this->hasErrors()) {
                return null;
            }
        }

        $organization = $organization
            ->withName($name);

        return $organization;
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    private function validate($name): bool
    {
        if (!$this->validateIsRequired($name) || !$this->validateSanityCheck($name)) {
            $this->addRequiredError('Name', 'name');
        }

        if ($this->hasErrors()) {
            return false;
        }

        if (!$this->validateLength($name, 3, 100)) {
            $this->addLengthError('Name', 3, 100, 'name');
        }

        if ($this->hasErrors()) {
            return false;
        }

        if (!$this->validateCharacterBlacklist($name, self::REGEX_CHARACTER_WHITESPACE)) {
            $this->addError(self::ERR_NAME_CHARACTERS, 'name');
        }

        return !$this->hasErrors();
    }
}
