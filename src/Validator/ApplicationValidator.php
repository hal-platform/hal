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
use Hal\Core\Entity\System\VersionControlProvider;
use Hal\Core\Type\VCSProviderEnum;
use Hal\UI\Service\GitHubService;
use Hal\UI\VersionControl\VCS;

class ApplicationValidator
{
    use ValidatorErrorTrait;
    use NewValidatorTrait;

    private const REGEX_CHARACTER_CLASS_GITHUB = '0-9a-zA-Z_.-';
    private const REGEX_CHARACTER_WHITESPACE = '\f\n\r\t\v';

    private const ERR_DESCRIPTION_CHARACTERS = 'Name must not contain tabs or newlines';
    private const ERR_DUPE_NAME = 'An application with this name already exists';
    private const ERR_INVALID_ORG = 'Please select an organization for this application';
    private const ERR_INVALID_VCS = 'Please select a version control provider for this application';

    // private const ERR_GITHUB_INVALID_OWNER = 'Invalid GitHub user or organization';
    private const ERR_GITHUB_INVALID_REPO = 'Invalid GitHub repository';

    /**
     * @var EntityRepository
     */
    private $applicationRepo;
    private $organizationRepo;
    private $vcsRepo;

    /**
     * @var VCS
     */
    private $vcs;

    /**
     * @param EntityManagerInterface $em
     * @param VCS $vcs
     */
    public function __construct(EntityManagerInterface $em, VCS $vcs)
    {
        $this->applicationRepo = $em->getRepository(Application::class);
        $this->organizationRepo = $em->getRepository(Organization::class);
        $this->vcsRepo = $em->getRepository(VersionControlProvider::class);

        $this->vcs = $vcs;
    }

    /**
     * @param string $name
     * @param string $organizationID
     * @param string $vcsID
     *
     * @return Application|null
     */
    public function isValid($name, $organizationID, $vcsID): ?Application
    {
        $this->resetErrors();

        $this->validate($name);

        if ($this->hasErrors()) {
            return null;
        }

        if ($app = $this->applicationRepo->findOneBy(['name' => $name])) {
            $this->addError(self::ERR_DUPE_NAME);
        }

        $organization = null;
        if ($organizationID && !$organization = $this->organizationRepo->find($organizationID)) {
            $this->addError(self::ERR_INVALID_ORG);
        }

        $vcs = null;
        if ($vcsID && !$vcs = $this->vcsRepo->find($vcsID)) {
            $this->addError(self::ERR_INVALID_VCS);
        }

        if ($this->hasErrors()) {
            return null;
        }

        $application = (new Application)
            ->withName($name)
            ->withOrganization($organization)
            ->withProvider($vcs);

        return $application;
    }

    /**
     * @param Application $application
     * @param string $name
     * @param string $organizationID
     * @param string $vcsID
     *
     * @return Application|null
     */
    public function isEditValid(Application $application, $name, $organizationID, $vcsID): ?Application
    {
        $this->resetErrors();

        $this->validate($name);

        if ($this->hasErrors()) {
            return null;
        }

        if ($application->name() !== $name) {
            if ($org = $this->applicationRepo->findOneBy(['name' => $name])) {
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

        $vcs = null;
        if ($vcsID && !$vcs = $this->vcsRepo->find($vcsID)) {
            $this->addError(self::ERR_INVALID_VCS);
        }

        if ($this->hasErrors()) {
            return null;
        }

        $application
            ->withName($name)
            ->withOrganization($organization)
            ->withProvider($vcs);

        return $application;
    }

    /**
     * @param Application $application
     * @param array $parameters
     *
     * @return Application|null
     */
    public function isVCSValid(Application $application, array $parameters): ?Application
    {
        $provider = $application->provider();
        if (!$provider) {
            return null;
        }

        $ghOwner = $parameters['gh_owner'] ?? '';
        $ghRepo = $parameters['gh_repo'] ?? '';
        $gitLink = $parameters['git_link'] ?? '';

        if ($provider->type() === VCSProviderEnum::TYPE_GITHUB_ENTERPRISE) {
            $baseURL = $provider->parameter('ghe.url');
            $token = $provider->parameter('ghe.token');

            // ideally we shouldn't need this
            if (!$baseURL || !$token) {
                $this->addError(self::ERR_INVALID_VCS);
                return null;
            }

        } elseif ($provider->type() === VCSProviderEnum::TYPE_GITHUB) {
            $token = $provider->parameter('gh.token');

            // ideally we shouldn't need this
            if (!$token) {
                $this->addError(self::ERR_INVALID_VCS);
                return null;
            }

        } elseif ($provider->type() === VCSProviderEnum::TYPE_GIT) {
            // in the future
            $this->addError('Git clones are not yet supported.');
            return null;

        } else {
            return null;
        }

        $github = $this->vcs->authenticate($provider);
        if (!$github) {
            return null;
        }

        $isValid = $this->validateGithubRepo($github, $ghOwner, $ghRepo);
        if (!$isValid) {
            return null;
        }

        $application
            ->withParameter('gh.owner', $ghOwner)
            ->withParameter('gh.repo', $ghRepo);

        // $application->parameter('git.link');

        return $application;
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

    /**
     * Parse user/repo from the provided input which may or may not be a full github URL.
     *
     * @param string $github
     *
     * @return array|null
     */
    // private function formatGitHubFromURL($github)
    // {
    //     $regex = implode('', [
    //         '@^',
    //         '(?:https?\:\/\/)?', # scheme is optional
    //         '(?:[[:ascii:]]+\/)?', # domain is optional
    //         '([' . self::REGEX_CHARACTER_CLASS_GITHUB . ']{1,100})',
    //         '\/',
    //         '([' . self::REGEX_CHARACTER_CLASS_GITHUB . ']{1,100}?)',
    //         '(?:\.git)?', # .git suffix is optional
    //         '$@'
    //     ]);

    //     if (!preg_match($regex, $github, $patterns)) {
    //         $this->addError(self::ERR_GITHUB_INVALID_REPO, 'github');
    //         return null;
    //     }

    //     array_shift($patterns);

    //     if (count($patterns) !== 2) {
    //         $this->addError(self::ERR_GITHUB_INVALID_REPO, 'github');
    //     }

    //     return $patterns;
    // }

    /**
     * @param GitHubService $github
     * @param string $owner
     * @param string $repo
     *
     * @return bool
     */
    private function validateGithubRepo(GitHubService $github, $owner, $repo)
    {
        if (!$github->repository($owner, $repo)) {
            $this->addError(self::ERR_GITHUB_INVALID_REPO, 'gh_owner');
            return false;
        }

        return true;
    }
}
