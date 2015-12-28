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
use QL\Hal\Core\Repository\DeploymentRepository;
use QL\Hal\Core\Repository\PushRepository;
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
     * @param ResponseFormatter $responseFormatter
     * @param Application $application
     * @param Environment $environment
     */
    public function __construct(
        EntityManagerInterface $em,
        PoolService $poolService,
        ResponseFormatter $responseFormatter,
        Application $application,
        Environment $environment
    ) {
        $this->poolService = $poolService;
        $this->responseFormatter = $responseFormatter;

        $this->application = $application;
        $this->environment = $environment;

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

        $payload = [
            'statuses' => $statuses,
            'view' => $this->getSelectedView()
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
