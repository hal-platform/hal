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
    use NewValidatorTrait;

    private const REGEX_CHARACTER_CLASS_NAME = '0-9a-z_.-';
    private const REGEX_CHARACTER_WHITESPACE = '\f\n\r\t\v';
    private const ERR_NAME_CHARACTERS = 'Name must contain only alphanumeric characters with periods (.), underscore (_), and dashes (-)';
    private const ERR_DESCRIPTION_CHARACTERS = 'Description must not contain tabs or newlines';
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
     * @param string $description
     *
     * @return Organization|null
     */
    public function isValid($name, $description): ?Organization
    {
        $this->resetErrors();

        $name = $this->sanitizeName($name);
        $this->validate($name, $description);

        if ($this->hasErrors()) {
            return null;
        }

        if ($org = $this->organizationRepo->findOneBy(['identifier' => $name])) {
            $this->addError(self::ERR_DUPE_NAME);
        }

        if ($this->hasErrors()) {
            return null;
        }

        $organization = (new Organization)
            ->withIdentifier($name)
            ->withName($description);

        return $organization;
    }

    /**
     * @param Organization $organization
     * @param string $name
     * @param string $description
     *
     * @return Organization|null
     */
    public function isEditValid(Organization $organization, $name, $description): ?Organization
    {
        $this->resetErrors();

        $name = $this->sanitizeName($name);
        $this->validate($name, $description);

        if ($this->hasErrors()) {
            return null;
        }

        if ($organization->identifier() !== $name) {
            if ($org = $this->organizationRepo->findOneBy(['identifier' => $name])) {
                $this->addError(self::ERR_DUPE_NAME);
            }

            if ($this->hasErrors()) {
                return null;
            }
        }

        $organization = $organization
            ->withIdentifier($name)
            ->withName($description);

        return $organization;
    }


    /**
     * @param string $name
     *
     * @return string
     */
    private function sanitizeName($name)
    {
        $name = strtolower($name);
        $name = preg_replace('/[^' . self::REGEX_CHARACTER_CLASS_NAME . ']/', '-', $name);
        $name = trim($name, '_.-');

        return $name;
    }

    /**
     * @param string $name
     * @param string $description
     *
     * @return bool
     */
    private function validate($name, $description): bool
    {
        if (!$this->validateIsRequired($name) || !$this->validateSanityCheck($name)) {
            $this->addRequiredError('Name', 'name');
        }

        if (!$this->validateIsRequired($description) || !$this->validateSanityCheck($description)) {
            $this->addRequiredError('Description', 'description');
        }

        if ($this->hasErrors()) {
            return false;
        }

        if (!$this->validateLength($name, 3, 30)) {
            $this->addLengthError('Name', 3, 30, 'name');
        }

        if (!$this->validateLength($description, 3, 100)) {
            $this->addLengthError('Description', 3, 100, 'description');
        }

        if ($this->hasErrors()) {
            return false;
        }

        if (!$this->validateCharacterWhitelist($name, self::REGEX_CHARACTER_CLASS_NAME)) {
            $this->addError(self::ERR_NAME_CHARACTERS, 'name');
        }

        if (!$this->validateCharacterBlacklist($description, self::REGEX_CHARACTER_WHITESPACE)) {
            $this->addError(self::ERR_DESCRIPTION_CHARACTERS, 'description');
        }

        return !$this->hasErrors();
    }
}
