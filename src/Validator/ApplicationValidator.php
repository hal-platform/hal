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
use Hal\Core\Entity\Application\GitHubApplication;
use Hal\Core\Entity\Organization;
use Hal\UI\Service\GitHubService;

class ApplicationValidator
{
    use ValidatorErrorTrait;
    use NewValidatorTrait;

    private const REGEX_CHARACTER_CLASS_NAME = '0-9a-z_.-';
    private const REGEX_CHARACTER_CLASS_GITHUB = '0-9a-zA-Z_.-';
    private const REGEX_CHARACTER_WHITESPACE = '\f\n\r\t\v';

    private const ERR_NAME_CHARACTERS = 'Name must contain only alphanumeric characters with periods (.), underscore (_), and dashes (-)';
    private const ERR_DESCRIPTION_CHARACTERS = 'Description must not contain tabs or newlines';
    private const ERR_DUPE_NAME = 'An application with this name already exists';
    private const ERR_INVALID_ORG = 'Please select an organization for this application';

    private const ERR_GITHUB_INVALID_OWNER = 'Invalid GitHub user or organization';
    private const ERR_GITHUB_INVALID_REPO = 'Invalid GitHub repository';

    /**
     * @var EntityRepository
     */
    private $applicationRepo;
    private $organizationRepo;

    /**
     * @var GitHubService
     */
    private $github;

    /**
     * @param EntityManagerInterface $em
     * @param GitHubService $github
     */
    public function __construct(EntityManagerInterface $em, GitHubService $github)
    {
        $this->applicationRepo = $em->getRepository(Application::class);
        $this->organizationRepo = $em->getRepository(Organization::class);

        $this->github = $github;
    }

    /**
     * @param string $name
     * @param string $description
     * @param string $githubRepo
     * @param string $organizationID
     *
     * @return Application|null
     */
    public function isValid($name, $description, $githubRepo, $organizationID): ?Application
    {
        $this->resetErrors();

        $name = $this->sanitizeName($name);
        $github = $this->formatGitHubFromURL($githubRepo);

        $this->validate($name, $description);

        if ($this->hasErrors()) {
            return null;
        }

        if ($app = $this->applicationRepo->findOneBy(['identifier' => $name])) {
            $this->addError(self::ERR_DUPE_NAME);
        }

        $organization = null;
        if ($organizationID && !$organization = $this->organizationRepo->find($organizationID)) {
            $this->addError(self::ERR_INVALID_ORG);
        }

        $github && $this->validateGithubRepo($github[0], $github[1]);

        if ($this->hasErrors()) {
            return null;
        }

        $application = (new Application)
            ->withIdentifier($name)
            ->withName($description)
            ->withOrganization($organization)
            ->withGitHub(new GitHubApplication($github[0], $github[1]));

        return $application;
    }

    /**
     * @param Application $application
     * @param string $name
     * @param string $description
     * @param string $githubRepo
     * @param string $organizationID
     *
     * @return Application|null
     */
    public function isEditValid(Application $application, $name, $description, $githubRepo, $organizationID): ?Application
    {
        $this->resetErrors();

        $name = $this->sanitizeName($name);
        $github = $this->formatGitHubFromURL($githubRepo);

        $this->validate($name, $description);

        if ($this->hasErrors()) {
            return null;
        }

        if ($application->identifier() !== $name) {
            if ($org = $this->applicationRepo->findOneBy(['identifier' => $name])) {
                $this->addError(self::ERR_DUPE_NAME);
            }

            if ($this->hasErrors()) {
                return null;
            }
        }

        $organization = null;
        if ($organizationID && !$organization = $this->organizationRepo->find($organizationID)) {
            $this->addError(self::ERR_INVALID_ORG);
        }

        $github && $this->validateGithubRepo($github[0], $github[1]);

        if ($this->hasErrors()) {
            return null;
        }

        $application
            ->withIdentifier($name)
            ->withName($description)
            ->withOrganization($organization)
            ->withGitHub(new GitHubApplication($github[0], $github[1]));

        return $application;
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

    /**
     * Parse user/repo from the provided input which may or may not be a full github URL.
     *
     * @param string $github
     *
     * @return array|null
     */
    private function formatGitHubFromURL($github)
    {
        $regex = implode('', [
            '@^',
            '(?:https?\:\/\/)?', # scheme is optional
            '(?:[[:ascii:]]+\/)?', # domain is optional
            '([' . self::REGEX_CHARACTER_CLASS_GITHUB . ']{1,100})',
            '\/',
            '([' . self::REGEX_CHARACTER_CLASS_GITHUB . ']{1,100}?)',
            '(?:\.git)?', # .git suffix is optional
            '$@'
        ]);

        if (!preg_match($regex, $github, $patterns)) {
            $this->addError(self::ERR_GITHUB_INVALID_REPO, 'github');
            return null;
        }

        array_shift($patterns);

        if (count($patterns) !== 2) {
            $this->addError(self::ERR_GITHUB_INVALID_REPO, 'github');
        }

        return $patterns;
    }

    /**
     * @param string $owner
     * @param string $repo
     *
     * @return void
     */
    private function validateGithubRepo($owner, $repo)
    {
        if (!$this->github->user($owner)) {
            $this->addError(self::ERR_GITHUB_INVALID_OWNER, 'github');

        // elseif here so we dont bother making 2 github calls if the first one failed
        } elseif (!$this->github->repository($owner, $repo)) {
            $this->addError(self::ERR_GITHUB_INVALID_REPO, 'github');
        }
    }
}
