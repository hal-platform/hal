<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Validator;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Hal\Core\Entity\Application;
use Hal\Core\Entity\Credential;
use Hal\Core\Entity\Environment;
use Hal\Core\Entity\Target;
use Hal\Core\Entity\TargetTemplate;
use Hal\UI\Validator\Targets\TargetValidatorInterface;
use Psr\Http\Message\ServerRequestInterface;

class TargetValidator
{
    use ValidatorErrorTrait;
    use ValidatorTrait;

    private const REGEX_CHARACTER_RELAXED_WHITESPACE = '\f\n\r\t\v';

    private const ERT_CHARACTERS_RELAXED_WHITESPACE = '%s must not contain tabs or newlines';

    private const ERR_TYPE_REQUIRED = 'Please select a deployment type.';
    private const ERR_INVALID_URL = 'URL is invalid.';
    private const ERR_INVALID_URL_SCHEME = 'URL scheme is invalid. Please use http or https.';
    private const ERR_INVALID_CREDENTIALS = 'Selected credential is invalid.';
    private const ERR_INVALID_TEMPLATE = 'Selected template is invalid.';

    /**
     * @var EntityRepository
     */
    private $templateRepo;
    private $credentialRepo;

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
        $this->templateRepo = $em->getRepository(TargetTemplate::class);
        $this->credentialRepo = $em->getRepository(Credential::class);

        $this->typeValidators = [];

        foreach ($typeValidators as $type => $validator) {
            $this->addTypeValidator($type, $validator);
        }
    }

    /**
     * @param string $type
     * @param TargetValidatorInterface $validator
     *
     * @return void
     */
    public function addTypeValidator($type, TargetValidatorInterface $validator): void
    {
        $this->typeValidators[$type] = $validator;
    }

    /**
     * @param Application $application
     * @param Environment $environment
     *
     * @param string $type
     * @param array $parameters
     *
     * @return Target|null
     */
    public function isValid(Application $application, Environment $environment, string $type, array $parameters): ?Target
    {
        $this->resetErrors();

        $templateID = $parameters['template'] ?? '';
        $credentialID = $parameters['credential'] ?? '';

        $name = trim($parameters['name'] ?? '');
        $url = trim($parameters['url'] ?? '');
        $context = trim($parameters['script_context'] ?? '');

        $this->validateRequired($name);

        if ($this->hasErrors()) {
            return null;
        }

        $url = $this->validateURL($url);
        $this->validateName($name);
        $this->validateContext($context);

        // stop validation if errors
        if ($this->hasErrors()) {
            return null;
        }

        $credential = null;
        if ($credentialID) {
            if (!$credential = $this->credentialRepo->findOneBy(['id' => $credentialID, 'isInternal' => false])) {
                $this->addError(self::ERR_INVALID_CREDENTIALS, 'credentials');
            }
        }

        $template = null;
        if ($templateID) {
            if (!$template = $this->credentialRepo->findOneBy(['id' => $templateID, 'environment' => $environment])) {
                $this->addError(self::ERR_INVALID_TEMPLATE, 'template');
            }
        }

        if (!isset($this->typeValidators[$type])) {
            $this->addError(self::ERR_TYPE_REQUIRED, 'deployment_type');
            return null;
        }

        // stop validation if errors
        if ($this->hasErrors()) {
            return null;
        }

        $validator = $this->typeValidators[$type];

        if (!$target = $validator->isValid($parameters)) {
            $this->importErrors($validator->errors());
            return null;
        }

        // stop validation if errors
        if ($this->hasErrors()) {
            return null;
        }

        $target
            ->withApplication($application)
            ->withEnvironment($environment)
            ->withTemplate($template)
            ->withType($type)
            ->withName($name)
            ->withURL($url)
            ->withCredential($credential)

            ->withParameter(Target::PARAM_CONTEXT, $context);

        return $target;
    }

    /**
     * @param Target $target
     * @param Environment $environment
     *
     * @param array $parameters
     *
     * @return Target|null
     */
    public function isEditValid(Target $target, Environment $environment, array $parameters): ?Target
    {
        $this->resetErrors();

        $type = $target->type();

        $templateID = $parameters['template'] ?? '';
        $credentialID = $parameters['credential'] ?? '';

        $name = trim($parameters['name'] ?? '');
        $url = trim($parameters['url'] ?? '');
        $context = trim($parameters['script_context'] ?? '');

        $this->validateRequired($name);

        if ($this->hasErrors()) {
            return null;
        }

        $url = $this->validateURL($url);
        $this->validateName($name);
        $this->validateContext($context);

        // stop validation if errors
        if ($this->hasErrors()) {
            return null;
        }

        $credential = null;
        if ($credentialID) {
            if (!$credential = $this->credentialRepo->findOneBy(['id' => $credentialID, 'isInternal' => false])) {
                $this->addError(self::ERR_INVALID_CREDENTIALS, 'credentials');
            }
        }

        $template = null;
        if ($templateID) {
            if (!$template = $this->credentialRepo->findOneBy(['id' => $templateID, 'environment' => $environment])) {
                $this->addError(self::ERR_INVALID_TEMPLATE, 'template');
            }
        }

        if (!isset($this->typeValidators[$type])) {
            $this->addError(self::ERR_TYPE_REQUIRED, 'deployment_type');
            return null;
        }

        // stop validation if errors
        if ($this->hasErrors()) {
            return null;
        }

        $validator = $this->typeValidators[$type];

        if (!$target = $validator->isEditValid($target, $parameters)) {
            $this->importErrors($validator->errors());
            return null;
        }

        // stop validation if errors
        if ($this->hasErrors()) {
            return null;
        }

        $target
            // ->withApplication($application)
            ->withEnvironment($environment)
            ->withTemplate($template)
            ->withName($name)
            ->withURL($url)
            ->withCredential($credential)

            ->withParameter(Target::PARAM_CONTEXT, $context);

        return $target;
    }

    /**
     * @param ServerRequestInterface $request
     * @param string $type
     * @param Target|null $target
     *
     * @return array
     */
    public function getTargetFormData(ServerRequestInterface $request, $type, ?Target $target): array
    {
        if (!isset($this->typeValidators[$type])) {
            return [];
        }

        $validator = $this->typeValidators[$type];

        return $validator->getFormData($request, $target);
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    private function validateRequired($groupID)
    {
        if (!$this->validateIsRequired($groupID) || !$this->validateSanityCheck($groupID)) {
            $this->addRequiredError('Name', 'name');
        }

        return $this->hasErrors();
    }

    /**
     * @param string $name
     *
     * @return void
     */
    private function validateName($name)
    {
        if (!$this->validateCharacterBlacklist($name, self::REGEX_CHARACTER_RELAXED_WHITESPACE)) {
            $error = sprintf(self::ERT_CHARACTERS_RELAXED_WHITESPACE, 'Name');
            $this->addError($error, 'name');
        }

        if (!$this->validateLength($name, 3, 100)) {
            $this->addLengthError('Name', 3, 100, 'name');
        }
    }

    /**
     * @param string $context
     *
     * @return void
     */
    private function validateContext($context)
    {
        if (strlen($context) > 0) {
            if (!$this->validateCharacterBlacklist($context, self::REGEX_CHARACTER_RELAXED_WHITESPACE)) {
                $error = sprintf(self::ERT_CHARACTERS_RELAXED_WHITESPACE, 'Script Context');
                $this->addError($error, 'script_context');
            }

            if (!$this->validateLength($context, 3, 100)) {
                $this->addLengthError('Script Context', 3, 100, 'script_context');
            }
        }
    }

    /**
     * @param string $url
     *
     * @return string
     */
    private function validateURL($url)
    {
        if (strlen($url) === 0) {
            return $url;
        }

        if (!$this->validateLength($url, 0, 200)) {
            $this->addLengthError($url, 0, 200, 'URL');
        }

        $scheme = parse_url($url, PHP_URL_SCHEME);
        if (!in_array($scheme, [null, 'http', 'https'], true)) {
            $this->addError(self::ERR_INVALID_URL_SCHEME, 'url');
        }

        if ($scheme === null) {
            $url = 'http://' . $url;
        }

        if ($this->hasErrors()) {
            return null;
        }

        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            $this->addError(self::ERR_INVALID_URL, 'url');
        }

        return $url;
    }
}
