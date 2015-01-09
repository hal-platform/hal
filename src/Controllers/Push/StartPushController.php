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
use QL\Hal\Slim\NotFound;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use Slim\Http\Request;
use Slim\Http\Response;

class StartPushController implements ControllerInterface
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
     * @type Request
     */
    private $request;

    /**
     * @type Response
     */
    private $response;

    /**
     * @type NotFound
     */
    private $notFound;

    /**
     * @type array
     */
    private $parameters;

    /**
     * @param TemplateInterface $template
     * @param BuildRepository $buildRepo
     * @param DeploymentRepository $deploymentRepo
     * @param ServerRepository $serverRepo
     * @param PushRepository $pushRepo
     * @param Request $request
     * @param Response $response
     * @param NotFound $notFound
     * @param array $parameters
     */
    public function __construct(
        TemplateInterface $template,
        BuildRepository $buildRepo,
        DeploymentRepository $deploymentRepo,
        ServerRepository $serverRepo,
        PushRepository $pushRepo,
        Request $request,
        Response $response,
        NotFound $notFound,
        array $parameters
    ) {
        $this->template = $template;
        $this->buildRepo = $buildRepo;
        $this->deploymentRepo = $deploymentRepo;
        $this->serverRepo = $serverRepo;
        $this->pushRepo = $pushRepo;

        $this->request = $request;
        $this->response = $response;
        $this->notFound = $notFound;
        $this->parameters = $parameters;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        $build = $this->buildRepo->find($this->parameters['build']);

        if (!$build || $build->getStatus() != 'Success') {
            return call_user_func($this->notFound);
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
            'selected' => $this->request->get('deployment'),
            'statuses' => $statuses
        ]);

        $this->response->setBody($rendered);
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
