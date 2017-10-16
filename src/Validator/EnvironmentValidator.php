<?php
/**
 * @copyright (c) 2017 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Validator;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Hal\Core\Entity\Environment;

class EnvironmentValidator
{
    use ValidatorErrorTrait;
    use NewValidatorTrait;

    private const REGEX_CHARACTER_CLASS_NAME = '0-9a-z_.-';
    private const REGEX_CHARACTER_WHITESPACE = '\f\n\r\t\v';
    private const ERR_NAME_CHARACTERS = 'Name must contain only alphanumeric characters with periods (.), underscore (_), and dashes (-)';
    private const ERR_DUPE_NAME = 'An environment with this name already exists';

    /**
     * @var EntityRepository
     */
    private $environmentRepo;

    /**
     * @param EntityManagerInterface $em
     */
    public function __construct(EntityManagerInterface $em)
    {
        $this->environmentRepo = $em->getRepository(Environment::class);
    }

    /**
     * @param string $name
     * @param bool $isProduction
     *
     * @return Environment|null
     */
    public function isValid($name, $isProduction): ?Environment
    {
        $this->resetErrors();

        $name = $this->sanitizeName($name);
        $isValidated = $this->validate($name);

        if (!$isValidated) {
            return null;
        }

        if ($org = $this->environmentRepo->findOneBy(['name' => $name])) {
            $this->addError(self::ERR_DUPE_NAME);
        }

        if (!$isValidated) {
            return null;
        }

        $environment = (new Environment)
            ->withName($name)
            ->withIsProduction($isProduction);

        return $environment;
    }

    /**
     * @param Environment $environment
     * @param string $name
     * @param bool $isProduction
     *
     * @return Environment|null
     */
    public function isEditValid(Environment $environment, $name, $isProduction): ?Environment
    {
        $this->resetErrors();

        $name = $this->sanitizeName($name);
        $isValidated = $this->validate($name);

        if (!$isValidated) {
            return null;
        }

        if ($environment->name() !== $name) {
            if ($org = $this->environmentRepo->findOneBy(['name' => $name])) {
                $this->addError(self::ERR_DUPE_NAME);
            }

            if (!$isValidated) {
                return null;
            }
        }

        $environment = $environment
            ->withName($name)
            ->withIsProduction($isProduction);

        return $environment;
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

        if (!$this->validateLength($name, 3, 20)) {
            $this->addLengthError('Name', 3, 20, 'name');
        }

        if ($this->hasErrors()) {
            return false;
        }

        if (!$this->validateCharacterWhitelist($name, self::REGEX_CHARACTER_CLASS_NAME)) {
            $this->addError(self::ERR_NAME_CHARACTERS, 'name');
        }

        return !$this->hasErrors();
    }
}
