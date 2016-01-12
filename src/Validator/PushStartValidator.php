<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Hal\Validator;

use Doctrine\ORM\EntityManagerInterface;
use QL\Hal\Core\Entity\Application;
use QL\Hal\Core\Entity\Build;
use QL\Hal\Core\Entity\Deployment;
use QL\Hal\Core\Entity\Environment;
use QL\Hal\Core\Entity\Process;
use QL\Hal\Core\Entity\Push;
use QL\Hal\Core\Entity\User;
use QL\Hal\Core\JobIdGenerator;
use QL\Hal\Core\Repository\DeploymentRepository;
use QL\Hal\Core\Type\EnumType\ServerEnum;
use QL\Hal\Service\PermissionService;

class PushStartValidator
{
    const ERR_NO_DEPS = 'You must select at least one deployment.';
    const ERR_NO_PERM = 'You attempted to push to "%s" but do not have permission.';

    const ERR_BAD_DEP = 'One or more of the selected deployments is invalid.';
    const ERR_IS_PENDING = 'Push to "%s" cannot be created, deployment already in progress.';
    const ERR_MISSING_CREDENTIALS = 'Attempted to initiate push to "%s", but credentials are missing.';

    /**
     * @type DeploymentRepository
     */
    private $deploymentRepo;

    /**
     * @type PermissionService
     */
    private $permissions;

    /**
     * @type JobIdGenerator
     */
    private $unique;

    /**
     * @type User
     */
    private $currentUser;

    /**
     * @type callable
     */
    private $random;

    /**
     * @type array
     */
    private $errors;

    /**
     * @param EntityManagerInterface $em
     * @param PermissionService $permissions
     * @param JobIdGenerator $unique
     * @param User $currentUser
     * @param callable $random
     */
    public function __construct(
        EntityManagerInterface $em,
        PermissionService $permissions,
        JobIdGenerator $unique,
        User $currentUser,
        callable $random
    ) {
        $this->permissions = $permissions;
        $this->unique = $unique;

        $this->currentUser = $currentUser;
        $this->random = $random;

        $this->deploymentRepo = $em->getRepository(Deployment::class);

        $this->errors = [];
    }

    /**
     * Verify ability to deploy and create pushes.
     *
     * @param Application $application
     * @param Environment $environment
     * @param Build $build
     * @param string[] $deployments
     *
     * @return Pushes[]|null
     */
    public function isValid(Application $application, Environment $environment, Build $build, $deployments)
    {
        $deployments = $this->isDeploymentsValid($application, $environment, $build, $deployments);
        if (!$deployments) {
            return null;
        }

        // Ensure no deployment has an active push (Waiting, Pushing)
        foreach ($deployments as $deployment) {
            $push = $deployment->push();
            if ($push && $push->isPending()) {
                $this->errors[] = sprintf(self::ERR_IS_PENDING, $deployment->formatPretty());
            }
        }

        if ($this->errors) return null;

        $pushes = [];
        foreach ($deployments as $deployment) {
            $id = $this->unique->generatePushId();

            $push = (new Push($id))
                ->withUser($this->currentUser)
                ->withBuild($build)
                ->withDeployment($deployment)
                ->withApplication($application);

            $pushes[] = $push;
        }

        return $pushes;
    }

    /**
     * Verify ability to deploy, and create child processes to push after build.
     *
     * @param Application $application
     * @param Environment $environment
     * @param Build $build
     * @param string[] $deployments
     *
     * @return Process[]|null
     */
    public function isProcessValid(Application $application, Environment $environment, Build $build, $deployments)
    {
        $deployments = $this->isDeploymentsValid($application, $environment, $build, $deployments);
        if (!$deployments) {
            return null;
        }

        $processes = [];
        foreach ($deployments as $deployment) {
            $id = call_user_func($this->random);

            $process = (new Process($id))
                ->withUser($this->currentUser)
                ->withParent($build)
                ->withChildType('Push')
                ->withContext([
                    'deployment' => $deployment->id()
                ]);

            $processes[] = $process;
        }

        return $processes;
    }

    /**
     * @param Application $application
     * @param Environment $environment
     * @param Build $build
     * @param string[] $deployments
     *
     * @return Deployments[]|null
     */
    private function isDeploymentsValid(Application $application, Environment $environment, Build $build, $deployments)
    {
        $this->errors = [];

        // Check for invalid requested deployments
        if (!is_array($deployments) || count($deployments) == 0) {
            $this->errors[] = self::ERR_NO_DEPS;
        }

        if ($this->errors) return;

        // Validate permission
        $canUserPush = $this->permissions->canUserPush($this->currentUser, $application, $environment);
        if (!$canUserPush) {
            $this->errors[] = sprintf(self::ERR_NO_PERM, $environment->name());
        }

        if ($this->errors) return;

        // Pull available deploys from DB for this env
        $availableDeployments = $this->deploymentRepo->getDeploymentsByApplicationEnvironment($application, $environment);
        if (!$availableDeployments) {
            $this->errors[] = self::ERR_NO_DEPS;
        }

        if ($this->errors) return;

        // Make sure requested deploys are verified against ones from DB
        $deploymentIDs = array_fill_keys($deployments, true);
        $selectedDeployments = [];
        foreach ($availableDeployments as $deployment) {
            if (isset($deploymentIDs[(string) $deployment->id()])) {
                $selectedDeployments[] = $deployment;

                // Error if AWS deployment has no credential
                if ($deployment->server()->type() !== ServerEnum::TYPE_RSYNC && !$deployment->credential()) {
                    $this->errors[] = sprintf(self::ERR_MISSING_CREDENTIALS, $deployment->formatPretty());
                }
            }
        }

        if (count($selectedDeployments) !== count($deployments)) {
            $this->errors[] = self::ERR_BAD_DEP;
        }

        if ($this->errors) return;

        return $selectedDeployments;
    }

    /**
     * @return array
     */
    public function errors()
    {
        return $this->errors;
    }
}
