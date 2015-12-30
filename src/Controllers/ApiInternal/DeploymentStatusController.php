<?php
/**
 * @copyright ©2015 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\ApiInternal;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use QL\Hal\Api\ResponseFormatter;
use QL\Hal\Core\Entity\Application;
use QL\Hal\Core\Entity\Deployment;
use QL\Hal\Core\Entity\Environment;
use QL\Hal\Core\Entity\Push;
use QL\Hal\Core\Entity\User;
use QL\Hal\Core\Repository\DeploymentRepository;
use QL\Hal\Core\Repository\PushRepository;
use QL\Hal\Service\PermissionService;
use QL\Hal\Service\PoolService;
use QL\Panthor\ControllerInterface;
use Slim\Http\Response;

class DeploymentStatusController implements ControllerInterface
{
    /**
     * @type PoolService
     */
    private $poolService;

    /**
     * @type PermissionService
     */
    private $permissionService;

    /**
     * @type ResponseFormatter
     */
    private $responseFormatter;

    /**
     * @type Application
     */
    private $application;

    /**
     * @type Environment
     */
    private $environment;

    /**
     * @type User
     */
    private $currentUser;

    /**
     * @type DeploymentRepository
     */
    private $deploymentRepo;

    /**
     * @type PushRepository
     */
    private $pushRepo;

    /**
     * @param EntityManagerInterface $em
     * @param PoolService $poolService
     * @param PermissionService $permissionService
     * @param ResponseFormatter $responseFormatter
     * @param Application $application
     * @param Environment $environment
     * @param User $currentUser
     */
    public function __construct(
        EntityManagerInterface $em,
        PoolService $poolService,
        PermissionService $permissionService,
        ResponseFormatter $responseFormatter,
        Application $application,
        Environment $environment,
        User $currentUser
    ) {
        $this->poolService = $poolService;
        $this->permissionService = $permissionService;
        $this->responseFormatter = $responseFormatter;

        $this->application = $application;
        $this->environment = $environment;
        $this->currentUser = $currentUser;

        $this->deploymentRepo = $em->getRepository(Deployment::class);
        $this->pushRepo = $em->getRepository(Push::class);
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        // Get deployments and latest pushes
        $available = $this->deploymentRepo->getDeploymentsByApplicationEnvironment($this->application, $this->environment);

        $statuses = [];
        foreach ($available as $deployment) {
            $push = $this->pushRepo->getMostRecentByDeployment($deployment);
            $build = ($push) ? $push->build() : null;

            $statuses[] = compact('deployment', 'push', 'build');
        }

        $canPush = $this->permissionService->canUserPush($this->currentUser, $this->application, $this->environment);

        $payload = [
            'statuses' => $statuses,
            'view' => $this->getSelectedView(),
            'permission' => $canPush
        ];

        $this->responseFormatter->respond($payload);
    }

    /**
     * @return array|void
     */
    private function getSelectedView()
    {
        // Get selected view user has saved
        $views = $this->poolService->getViews($this->application, $this->environment);
        $selectedViewID = $this->poolService->findSelectedView($this->application, $this->environment, $views);

        foreach ($views as $id => $view) {
            if ($selectedViewID === $id) {
                return $view;
            }
        }

        return null;
    }
}
