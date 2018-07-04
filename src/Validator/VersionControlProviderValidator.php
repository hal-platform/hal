<?php
/**
 * @copyright (c) 2018 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Validator;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Hal\Core\Entity\System\VersionControlProvider;
use Hal\UI\Validator\VersionControl\VersionControlValidatorInterface;
use Psr\Http\Message\ServerRequestInterface;

class VersionControlProviderValidator
{
    use ValidatorErrorTrait;
    use ValidatorTrait;

    private const REGEX_CHARACTER_RELAXED_WHITESPACE = '\f\n\r\t\v';

    private const ERT_CHARACTERS_RELAXED_WHITESPACE = '%s must not contain tabs or newlines';

    private const ERR_DUPE_NAME = 'A VCS provider with this name already exists';
    private const ERR_TYPE_REQUIRED = 'Please select a version control type.';

    /**
     * @var EntityRepository
     */
    private $vcsRepo;

    /**
     * @var array
     */
    private $typeValidators;

    /**
     * @param EntityManagerInterface $em
     * @param array $typeValidators
     */
    public function __construct(EntityManagerInterface $em, array $typeValidators = [])
    {
        $this->vcsRepo = $em->getRepository(VersionControlProvider::class);

        $this->typeValidators = [];

        foreach ($typeValidators as $type => $validator) {
            $this->addTypeValidator($type, $validator);
        }
    }

    /**
     * @param string $type
     * @param VersionControlValidatorInterface $validator
     *
     * @return void
     */
    public function addTypeValidator($type, VersionControlValidatorInterface $validator): void
    {
        $this->typeValidators[$type] = $validator;
    }

    /**
     * @param string $type
     * @param array $parameters
     *
     * @return VersionControlProvider|null
     */
    public function isValid(string $type, array $parameters): ?VersionControlProvider
    {
        $this->resetErrors();

        $name = trim($parameters['name'] ?? '');

        $this->validateName($name);

        if (!isset($this->typeValidators[$type])) {
            $this->addError(self::ERR_TYPE_REQUIRED, 'vcs_type');
            return null;
        }

        // stop validation if errors
        if ($this->hasErrors()) {
            return null;
        }

        if ($vcs = $this->vcsRepo->findOneBy(['name' => $name])) {
            $this->addError(self::ERR_DUPE_NAME);
        }

        // stop validation if errors
        if ($this->hasErrors()) {
            return null;
        }

        $validator = $this->typeValidators[$type];
        if (!$provider = $validator->isValid($parameters)) {
            $this->importErrors($validator->errors());
            return null;
        }

        $provider
            ->withType($type)
            ->withName($name);

        return $provider;
    }

    /**
     * @param VersionControlProvider $provider
     * @param array $parameters
     *
     * @return VersionControlProvider|null
     */
    public function isEditValid(VersionControlProvider $provider, array $parameters): ?VersionControlProvider
    {
        $this->resetErrors();

        $type = $provider->type();

        $name = trim($parameters['name'] ?? '');

        $this->validateName($name);

        if (!isset($this->typeValidators[$type])) {
            $this->addError(self::ERR_TYPE_REQUIRED, 'vcs_type');
            return null;
        }

        // stop validation if errors
        if ($this->hasErrors()) {
            return null;
        }

        if ($provider->name() !== $name) {
            if ($vcs = $this->vcsRepo->findOneBy(['name' => $name])) {
                $this->addError(self::ERR_DUPE_NAME);
            }
        }

        // stop validation if errors
        if ($this->hasErrors()) {
            return null;
        }

        $validator = $this->typeValidators[$type];
        if (!$provider = $validator->isEditValid($provider, $parameters)) {
            $this->importErrors($validator->errors());
            return null;
        }

        $provider
            ->withType($type)
            ->withName($name);

        return $provider;
    }

    /**
     * @param ServerRequestInterface $request
     * @param VersionControlProvider|null $provider
     *
     * @return array
     */
    public function getFormData(ServerRequestInterface $request, ?VersionControlProvider $provider): array
    {
        $data = $request->getParsedBody();

        $type = $data['vcs_type'] ?? '';

        if ($provider) {
            $type = $provider->type();
        }

        if ($provider && $request->getMethod() !== 'POST') {
            $data['name'] = $provider->name();
        }

        $form = [
            'vcs_type' => $type,

            'name' => $data['name'] ?? '',
        ];

        if (!isset($this->typeValidators[$type])) {
            return $form;
        }

        $validator = $this->typeValidators[$type];

        return $form + $validator->getFormData($request, $provider);
    }

    /**
     * @param string $name
     *
     * @return void
     */
    private function validateName($name)
    {
        if (!$this->validateIsRequired($name) || !$this->validateSanityCheck($name)) {
            $this->addRequiredError('Name', 'name');
        }

        if ($this->hasErrors()) {
            return;
        }

        if (!$this->validateCharacterBlacklist($name, self::REGEX_CHARACTER_RELAXED_WHITESPACE)) {
            $error = sprintf(self::ERT_CHARACTERS_RELAXED_WHITESPACE, 'Name');
            $this->addError($error, 'name');
        }

        if (!$this->validateLength($name, 3, 100)) {
            $this->addLengthError('Name', 3, 100, 'name');
        }
    }
}
