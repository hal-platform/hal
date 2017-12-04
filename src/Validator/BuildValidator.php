<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Validator;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Hal\Core\Type\JobStatusEnum;
use Hal\UI\Security\AuthorizationService;
use Hal\UI\Service\GitHubService;
use Hal\Core\Entity\Application;
use Hal\Core\Entity\Build;
use Hal\Core\Entity\Environment;
use Hal\Core\Entity\User;

class BuildValidator
{
    use ValidatorErrorTrait;
    use NewValidatorTrait;

    const ERR_NO_PERMISSION = 'You do not have permission to create a build for this application.';
    const ERR_UNKNOWN_REF = 'You must select a valid git reference.';
    const ERR_UNKNOWN_ENV = 'You must select a valid environment.';

    /**
     * Valid entries:
     *
     * - pr#500
     * - pr 500
     * - pull 500
     * - pull #500
     * - pull request 500
     * - pull request #500
     */
    const REGEX_PULL = '/^(?:pull|pr|pull request)(?: )?(?:#)?([\d]+)$/i';

    /**
     * @var EntityRepository
     */
    private $environmentRepo;
    private $buildRepo;

    /**
     * @var GitHubService
     */
    private $github;

    /**
     * @var AuthorizationService
     */
    private $authorizationService;

    /**
     * @param EntityManagerInterface $em
     * @param GitHubService $github
     * @param AuthorizationService $authorizationService
     */
    public function __construct(
        EntityManagerInterface $em,
        GitHubService $github,
        AuthorizationService $authorizationService
    ) {
        $this->buildRepo = $em->getRepository(Build::class);
        $this->environmentRepo = $em->getRepository(Environment::class);

        $this->github = $github;
        $this->authorizationService = $authorizationService;
    }

    /**
     * @param Application $application
     * @param User $user
     * @param string $environmentID
     * @param string $gitReference
     * @param string $gitSearch
     *
     * @return Build|null
     */
    public function isValid(Application $application, User $user, $environmentID, $gitReference, $gitSearch)
    {
        $this->resetErrors();

        // gitref,reference
            // pull/*       - pull request
            // tag/*        - tag
            // *            - branch
        // search
            // [a-f]{40}    - commit
            // pr \d        - pull request, See validator for full pull request regex

        // Git reference cascades through these options:
        // 1. "reference" - a radio option selected by user
        // 2. "search" - a search query provided by user
        $reference = $this->parseSubmittedRef($gitReference, $gitSearch);

        if (!$this->validateIsRequired($reference) || !$this->validateSanityCheck($reference)) {
            $this->addError(self::ERR_UNKNOWN_REF, 'reference');
        }

        if ($this->hasErrors()) {
            return null;
        }

        $env = null;
        if ($environmentID && !$env = $this->environmentRepo->find($environmentID)) {
            # attempt search by name
            if (!$env = $this->environmentRepo->findOneBy(['name' => $environmentID])) {
                $this->addError(self::ERR_UNKNOWN_ENV, 'environment');
            }
        }

        if ($this->hasErrors()) {
            return null;
        }

        if (!$this->authorizationService->getUserAuthorizations($user)->canBuild($application)) {
            $this->addError(self::ERR_NO_PERMISSION);
        }

        if ($this->hasErrors()) {
            return null;
        }

        if (!$ref = $this->github->resolve($application->github()->owner(), $application->github()->repository(), $reference)) {
            $this->addError(self::ERR_UNKNOWN_REF, 'reference');
        }

        if ($this->hasErrors()) {
            return null;
        }

        list($reference, $commit) = $ref;
        if ($reference === 'commit') {
            $reference = $commit;
        }

        $build = (new Build())
            ->withStatus(JobStatusEnum::TYPE_PENDING)
            ->withReference($reference)
            ->withCommit($commit)

            ->withUser($user)
            ->withApplication($application)
            ->withEnvironment($env);

        return $build;
    }

    /**
     * @param string $selectedOption
     * @param string $search
     *
     * @return string
     */
    private function parseSubmittedRef($selectedOption, $search)
    {
        // if user selected a radio, use it
        if ($selectedOption) {
            return $selectedOption;
        }

        // search query is commit sha
        if (preg_match(GitHubService::REGEX_COMMIT, $search) === 1) {
            return $search;
        }

        // search query is pull request
        if (preg_match(self::REGEX_PULL, $search, $matches) === 1) {
            return sprintf('pull/%d', array_pop($matches));
        }

        return $search;
    }
}
