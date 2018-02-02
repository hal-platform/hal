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
use Hal\Core\Entity\Target;
use Hal\Core\Entity\Environment;
use Hal\Core\Entity\ScheduledAction;
use Hal\Core\Entity\User;
use Hal\Core\Entity\JobType\Build;
use Hal\Core\Entity\JobType\Release;
use Hal\Core\Type\JobStatusEnum;
use Hal\Core\Type\ScheduledActionStatusEnum;
use Hal\Core\Repository\TargetRepository;
use Hal\UI\Security\AuthorizationService;

class ReleaseValidator
{
    use ValidatorErrorTrait;
    use ValidatorTrait;

    const ERR_NO_TARGETS = 'You must select at least one target.';
    const ERR_NO_PERM = 'You attempted to push to "%s" but do not have permission.';

    const ERR_BAD_DEP = 'One or more of the selected targets is invalid.';
    const ERR_IS_PENDING = 'Release to "%s" cannot be created, release already in progress.';
    const ERR_MISSING_CREDENTIALS = 'Attempted to initiate push to "%s", but credentials are missing.';

    /**
     * @var EntityRepository
     */
    private $targetRepo;

    /**
     * @var AuthorizationService
     */
    private $authorizationService;

    /**
     * @param EntityManagerInterface $em
     * @param AuthorizationService $authorizationService
     */
    public function __construct(EntityManagerInterface $em, AuthorizationService $authorizationService)
    {
        $this->authorizationService = $authorizationService;

        $this->targetRepo = $em->getRepository(Target::class);
    }

    /**
     * Verify ability to deploy and create pushes.
     *
     * @param Application $application
     * @param User $user
     * @param Environment $environment
     * @param Build $build
     * @param array $targets
     *
     * @return array|null
     */
    public function isValid(Application $application, User $user, Environment $environment, Build $build, $targets)
    {
        // Validate permission
        $userAuths = $this->authorizationService->getUserAuthorizations($user);
        if (!$canUserDeploy = $userAuths->canDeploy($application, $environment)) {
            $this->addError(sprintf(self::ERR_NO_PERM, $environment->name()));
        }

        if ($this->hasErrors()) {
            return null;
        }

        $targets = $this->isTargetsValid($application, $user, $environment, $build, $targets);
        if (!$targets) {
            return null;
        }

        // Ensure no target has an active deployment (pending, running)
        foreach ($targets as $target) {
            $release = $target->lastJob();
            if ($release && $release->inProgress()) {
                $this->addError(sprintf(self::ERR_IS_PENDING, $target->name()));
            }
        }

        if ($this->hasErrors()) {
            return null;
        }

        $releases = [];
        foreach ($targets as $target) {
            $release = (new Release)
                ->withStatus(JobStatusEnum::TYPE_PENDING)
                ->withBuild($build)
                ->withTarget($target)

                ->withUser($user)
                ->withApplication($application)
                ->withEnvironment($environment);

            $releases[] = $release;
        }

        return $releases;
    }

    /**
     * Verify ability to deploy, and create scheduled actions to push after build.
     *
     * @param Application $application
     * @param User $user
     * @param Environment $environment
     * @param Build $build
     * @param array $targets
     *
     * @return array|null
     */
    public function isScheduledJobValid(Application $application, User $user, Environment $environment, Build $build, $targets)
    {
        $targets = $this->isTargetsValid($application, $user, $environment, $build, $targets);
        if (!$targets) {
            return null;
        }

        $processes = [];
        foreach ($targets as $target) {
            $process = (new ScheduledAction)
                ->withStatus(ScheduledActionStatusEnum::TYPE_PENDING)

                ->withUser($user)
                ->withTriggerJob($build)
                ->withParameters([
                    'entity' => 'Release',
                    'condition' => 'success',
                    'target_id' => $target->id()
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
     * @param array $targets
     *
     * @return array|null
     */
    private function isTargetsValid(Application $application, User $user, Environment $environment, Build $build, $targets)
    {
        $this->resetErrors();

        // Check for invalid requested deployments
        if (!is_array($targets) || count($targets) == 0) {
            $this->addError(self::ERR_NO_TARGETS);
        }

        if ($this->hasErrors()) {
            return null;
        }

        // Pull available deploys from DB for this env
        $availableTargets = $this->targetRepo->findBy(['application' => $application, 'environment' => $environment]);
        if (!$availableTargets) {
            $this->addError(self::ERR_NO_TARGETS);
        }

        if ($this->hasErrors()) {
            return null;
        }

        // Make sure requested deploys are verified against ones from DB
        $targetIDs = array_fill_keys($targets, true);

        $selectedTargets = [];
        foreach ($availableTargets as $target) {
            if (isset($targetIDs[$target->id()])) {
                $selectedTargets[] = $target;

                // Error if AWS deployment has no credential
                if ($target->isAWS() && !$target->credential()) {
                    $this->addError(sprintf(self::ERR_MISSING_CREDENTIALS, $target->name()));
                }
            }
        }

        if (count($selectedTargets) !== count($targets)) {
            $this->addError(self::ERR_BAD_DEP);
        }

        if ($this->hasErrors()) {
            return null;
        }

        return $selectedTargets;
    }
}
