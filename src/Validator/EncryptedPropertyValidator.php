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
use Hal\Core\Entity\EncryptedProperty;
use Hal\Core\Entity\Environment;
use Hal\Core\Crypto\Encryption;

/**
 * Validates an Encrypted Property
 */
class EncryptedPropertyValidator
{
    use ValidatorErrorTrait;
    use ValidatorTrait;

    private const REGEX_CHARACTER_CLASS_NAME = '0-9A-Z_';
    private const REGEX_CHARACTER_WHITESPACE = '\f\n\r\t\v';

    private const ERR_NAME_CHARACTERS = 'Property name must consist of letters, numbers, and underscores only.';
    private const ERR_VALUE_CHARACTERS = 'Data must not have newlines or tabs.';
    private const ERR_DUPE_NAME = 'This property is already set for this environment.';
    private const ERR_INVALID_ENVIRONMENT = 'Please select an environment.';

    /**
     * @var EntityRepository
     */
    private $encryptedPropertyRepo;
    private $environmentRepo;

    /**
     * @var Encryption
     */
    private $encryption;

    /**
     * @param EntityManagerInterface $em
     */
    public function __construct(EntityManagerInterface $em, Encryption $encryption)
    {
        $this->encryptedPropertyRepo = $em->getRepository(EncryptedProperty::class);
        $this->environmentRepo = $em->getRepository(Environment::class);
        $this->encryption = $encryption;
    }

    /**
     * @param string $name
     * @param string $value
     * @param string $environmentID
     *
     * @return EncryptedProperty|null
     */
    public function isValid(Application $application, $environmentID, $name, $value): ?EncryptedProperty
    {
        $this->resetErrors();

        $name = $this->sanitizeName($name);
        $this->validate($environmentID, $name, $value);

        if ($this->hasErrors()) {
            return null;
        }

        if ($environmentID === 'global') {
            $environment = null;
        } else {
            if (!$environment = $this->environmentRepo->find($environmentID)) {
                $this->addError(self::ERR_INVALID_ENVIRONMENT, 'environment');
            }
        }

        if ($this->hasErrors()) {
            return null;
        }

        $prop = $this->encryptedPropertyRepo->findOneBy([
            'name' => $name,
            'application' => $application,
            'environment' => $environment,
        ]);

        if ($prop) {
            $this->addError(self::ERR_DUPE_NAME, 'name');
        }

        if ($this->hasErrors()) {
            return null;
        }

        $encrypted = $this->encryption->encrypt($value);
        $encryptedProperty = (new EncryptedProperty)
            ->withApplication($application)
            ->withName($name)
            ->withSecret($encrypted);

        if ($environment) {
            $encryptedProperty->withEnvironment($environment);
        }

        return $encryptedProperty;
    }

    /**
     * @param string $name
     *
     * @return string
     */
    private function sanitizeName($name)
    {
        $name = strtoupper($name);

        return $name;
    }

    /**
     * @param string $environmentID
     * @param string $name
     * @param string $value
     *
     * @return bool
     */
    private function validate($environmentID, $name, $value): bool
    {
        if (!$environmentID) {
            $this->addError(self::ERR_INVALID_ENVIRONMENT, 'environment');
        }

        if (!$this->validateIsRequired($name) || !$this->validateSanityCheck($name)) {
            $this->addRequiredError('Name', 'name');
        }

        if (!$this->validateIsRequired($value) || !$this->validateSanityCheck($value)) {
            $this->addRequiredError('Value', 'value');
        }

        if ($this->hasErrors()) {
            return false;
        }

        if (!$this->validateLength($name, 1, 64)) {
            $this->addLengthError('Name', 1, 64, 'name');
        }

        if (!$this->validateLength($value, 1, 200)) {
            $this->addLengthError('Value', 1, 200, 'value');
        }

        if ($this->hasErrors()) {
            return false;
        }

        if (!$this->validateCharacterWhitelist($name, self::REGEX_CHARACTER_CLASS_NAME)) {
            $this->addError(self::ERR_NAME_CHARACTERS, 'name');
        }

        if (!$this->validateCharacterBlacklist($value, self::REGEX_CHARACTER_WHITESPACE)) {
            $this->addError(self::ERR_VALUE_CHARACTERS, 'value');
        }

        return !$this->hasErrors();
    }
}
