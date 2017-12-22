<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Validator;

use Doctrine\ORM\EntityManagerInterface;
use Hal\Core\Entity\Application;
use Hal\Core\Entity\Build;
use Hal\Core\Entity\Target;
use Hal\Core\Entity\Environment;
use Hal\Core\Entity\JobProcess;
use Hal\Core\Entity\Release;
use Hal\Core\Entity\User;
use Hal\Core\Repository\TargetRepository;
use Hal\UI\Security\AuthorizationService;

class ReleaseValidator
{
    use NewValidatorTrait;
    use ValidatorErrorTrait;

    const ERR_NO_DEPS = 'You must select at least one target.';
    const ERR_NO_PERM = 'You attempted to push to "%s" but do not have permission.';

    const ERR_BAD_DEP = 'One or more of the selected targets is invalid.';
    const ERR_IS_PENDING = 'Release to "%s" cannot be created, release already in progress.';
    const ERR_MISSING_CREDENTIALS = 'Attempted to initiate push to "%s", but credentials are missing.';

    /**
     * @var TargetRepository
     */
    private $targetRepository;

    /**
     * @var AuthorizationService
     */
    private $authorizationService;

    /**
     * @param EntityManagerInterface $em
     * @param AuthorizationService $authorizationService
     */
    public function __construct(
        EntityManagerInterface $em,
        AuthorizationService $authorizationService
    ) {
        $this->authorizationService = $authorizationService;

        $this->targetRepository = $em->getRepository(Target::class);
    }

    /**
     * Verify ability to deploy and create pushes.
     *
     * @param Application $application
     * @param User $user
     * @param Environment $environment
     * @param Build $build
     * @param string[] $targets
     *
     * @return Release[]|null
     */
    public function isValid(Application $application, User $user, Environment $environment, Build $build, $targets)
    {
        $targets = $this->isTargetsValid($application, $user, $environment, $build, $targets);
        if (!$targets) {
            return null;
        }

        // Ensure no deployment has an active push (Waiting, Pushing)
        foreach ($targets as $target) {
            $release = $target->release();
            if ($release && $release->inProgress()) {
                $this->addError(sprintf(self::ERR_IS_PENDING, $target->format()));
            }
        }

        if ($this->hasErrors()) {
            return null;
        }

        $releases = [];
        foreach ($targets as $target) {
            $release = (new Release)
                ->withUser($user)
                ->withBuild($build)
                ->withTarget($target)
                ->withApplication($application);

            $releases[] = $release;
        }

        return $releases;
    }

    /**
     * Verify ability to deploy, and create child processes to push after build.
     *
     * @param Application $application
     * @param User $user
     * @param Environment $environment
     * @param Build $build
     * @param string[] $targets
     *
     * @return JobProcess[]|null
     */
    public function isProcessValid(Application $application, User $user, Environment $environment, Build $build, $targets)
    {
        $targets = $this->isTargetsValid($application, $user, $environment, $build, $targets);
        if (!$targets) {
            return null;
        }

        $processes = [];
        foreach ($targets as $target) {
            $process = (new JobProcess)
                ->withUser($user)
                ->withParent($build)
                ->withChildType('Release')
                ->withParameters([
                    'target' => $target->id()
                ]);

            $processes[] = $process;
        }

        return $processes;
    }

    /**
     * @param Application $application
     * @param User $user
     * @param Environment $environment
     * @param Build $build
     * @param string[] $targets
     *
     * @return Target[]|null
     */
    private function isTargetsValid(
        Application $application,
        User $user,
        Environment $environment,
        Build $build,
        $targets
    ) {
        $this->resetErrors();

        // Check for invalid requested deployments
        if (!is_array($targets) || count($targets) == 0) {
            $this->addError(self::ERR_NO_DEPS);
        }

        if ($this->hasErrors()) {
            return null;
        }

        // Validate permission
        $canUserPush = $this->authorizationService->getUserAuthorizations($user)->canDeploy($application, $environment);
        if (!$canUserPush) {
            $this->addError(sprintf(self::ERR_NO_PERM, $environment->name()));
        }

        if ($this->hasErrors()) {
            return null;
        }

        // Pull available deploys from DB for this env
        $availableTargets = $this->targetRepository->getByApplicationAndEnvironment($application, $environment);
        if (!$availableTargets) {
            $this->addError(self::ERR_NO_DEPS);
        }

        if ($this->hasErrors()) {
            return null;
        }


        // Make sure requested deploys are verified against ones from DB
        $targetIDs = array_fill_keys($targets, true);

        $selectedReleases = [];
        foreach ($availableTargets as $target) {
            if (isset($targetIDs[(string)$target->id()])) {
                $selectedReleases[] = $target;

                // Error if AWS deployment has no credential
                if ($target->group()->isAWS() && !$target->credential()) {
                    $this->addError(sprintf(self::ERR_MISSING_CREDENTIALS, $target->format()));
                }
            }
        }

        if (count($selectedReleases) !== count($targets)) {
            $this->addError(self::ERR_BAD_DEP);
        }

        if ($this->hasErrors()) {
            return null;
        }

        return $selectedReleases;
    }
}
