<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Push;

use QL\Hal\Core\Entity\Build;
use QL\Hal\Core\Entity\Deployment;
use QL\Hal\Core\Entity\Push;
use QL\Hal\Core\Entity\Repository\BuildRepository;
use QL\Hal\Core\Entity\Repository\DeploymentRepository;
use QL\Hal\Core\Entity\Repository\PushRepository;
use QL\Hal\Core\Entity\Repository\ServerRepository;
use QL\Panthor\TemplateInterface;
use Slim\Http\Request;
use Slim\Http\Response;

class PushStartController
{
    /**
     * @type TemplateInterface
     */
    private $template;

    /**
     * @type BuildRepository
     */
    private $buildRepo;

    /**
     * @type DeploymentRepository
     */
    private $deploymentRepo;

    /**
     * @type ServerRepository
     */
    private $serverRepo;

    /**
     * @type PushRepository
     */
    private $pushRepo;

    /**
     * @param TemplateInterface $template
     * @param BuildRepository $buildRepo
     * @param DeploymentRepository $deploymentRepo
     * @param ServerRepository $serverRepo
     * @param PushRepository $pushRepo
     */
    public function __construct(
        TemplateInterface $template,
        BuildRepository $buildRepo,
        DeploymentRepository $deploymentRepo,
        ServerRepository $serverRepo,
        PushRepository $pushRepo
    ) {
        $this->template = $template;
        $this->buildRepo = $buildRepo;
        $this->deploymentRepo = $deploymentRepo;
        $this->serverRepo = $serverRepo;
        $this->pushRepo = $pushRepo;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $params
     * @param callable $notFound
     */
    public function __invoke(Request $request, Response $response, array $params = [], callable $notFound = null)
    {
        $build = $this->buildRepo->find($params['build']);

        if (!$build || $build->getStatus() != 'Success') {
            return call_user_func($notFound);
        }

        $deployments = $this->getDeploymentsForBuild($build);

        $statuses = [];
        foreach ($deployments as $deployment) {

            $latest = $this->pushRepo->getMostRecentByDeployment($deployment);
            if ($latest && $latest->getStatus() === 'Success') {
                $success = $latest;
            } else {
                $success = $this->pushRepo->getMostRecentSuccessByDeployment($deployment);
            }

            $statuses[] = [
                'deployment' => $deployment,
                'latest' => $latest,
                'success' => $success
            ];
        }

        $rendered = $this->template->render([
            'build' => $build,
            'selected' => $request->get('deployment'),
            'statuses' => $statuses
        ]);

        $response->setBody($rendered);
    }

    /**
     * Get the deployments a build can be deployed to.
     *
     * @todo Move to repository
     *
     * @param Build $build
     *
     * @return Deployments[]
     */
    private function getDeploymentsForBuild(Build $build)
    {
        $servers = $this->serverRepo->findBy(['environment' => $build->getEnvironment()]);

        $criteria = [
            'repository' => $build->getRepository(),
            'server' => $servers
        ];

        return $this->deploymentRepo->findBy($criteria, ['server' => 'ASC']);
    }
}
