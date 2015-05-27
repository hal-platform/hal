<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use QL\Hal\Core\Entity\Deployment;
use QL\Hal\Core\Entity\Repository;
use QL\Hal\Core\Repository\DeploymentRepository;
use QL\Hal\Helpers\SortingHelperTrait;
use QL\Hal\Services\ElasticBeanstalkService;
use QL\Hal\Services\PermissionsService;
use QL\Kraken\Core\Entity\Application as KrakenApplication;
use QL\Panthor\Slim\NotFound;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use Slim\Http\Response;

class RepositoryController implements ControllerInterface
{
    use SortingHelperTrait;

    /**
     * @type TemplateInterface
     */
    private $template;

    /**
     * @type EntityRepository
     */
    private $repoRepo;

    /**
     * @type DeploymentRepository
     */
    private $deploymentRepo;

    /**
     * @type PermissionsService
     */
    private $permissions;

    /**
     * @type EntityRepository
     */
    private $krakenRepo;

    /**
     * @type ElasticBeanstalkService
     */
    private $ebService;

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
     * @param EntityManagerInterface $em,
     * @param PermissionsService $permissions
     * @param ElasticBeanstalkService $ebService
     * @param Response $response
     * @param NotFound $notFound
     * @param array $parameters
     */
    public function __construct(
        TemplateInterface $template,
        EntityManagerInterface $em,
        PermissionsService $permissions,
        ElasticBeanstalkService $ebService,
        Response $response,
        NotFound $notFound,
        array $parameters
    ) {
        $this->template = $template;

        $this->repoRepo = $em->getRepository(Repository::CLASS);
        $this->deploymentRepo = $em->getRepository(Deployment::CLASS);
        $this->krakenRepo = $em->getRepository(KrakenApplication::CLASS);

        $this->permissions = $permissions;
        $this->ebService = $ebService;

        $this->response = $response;
        $this->notFound = $notFound;
        $this->parameters = $parameters;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        if (!$repo = $this->repoRepo->find($this->parameters['id'])) {
            return call_user_func($this->notFound);
        }

        $krakenApp = $this->krakenRepo->findOneBy(['halApplication' => $repo]);

        $deployments = $this->deploymentRepo->findBy(['repository' => $repo]);
        $environmentalized = $this->environmentalizeDeployments($deployments);
        $ebEnvironments = $this->ebService->getEnvironmentsByDeployments($deployments);
        $deploymentCount = count($deployments);

        foreach ($environmentalized as $env => &$deployments) {
            foreach ($deployments as &$deployment) {
                $ebEnv = '';
                if (isset($ebEnvironments[$deployment->getId()])) {
                    $ebEnv = $ebEnvironments[$deployment->getId()];
                }

                $deployment = [
                    'deployment' => $deployment,
                    'eb_environment' => $ebEnv
                ];
            }
        }

        $rendered = $this->template->render([
            'repository' => $repo,
            'deployment_environments' => $environmentalized,
            'deployment_count' => $deploymentCount,
            'permissions' => $this->permissions->repositoryPermissionPairs($repo->getKey()),
            'kraken' => $krakenApp
        ]);

        $this->response->setBody($rendered);
    }

    /**
     * @param Deployment[] $deployments
     * @return array
     */
    private function environmentalizeDeployments(array $deployments)
    {
        // should be using server.order instead
        $environments = [
            'dev' => [],
            'test' => [],
            'beta' => [],
            'prod' => []
        ];

        foreach ($deployments as $deployment) {
            $env = $deployment->getServer()->getEnvironment()->getKey();

            if (!array_key_exists($env, $environments)) {
                $environments[$env] = [];
            }

            $environments[$env][] = $deployment;
        }

        $sorter = $this->deploymentSorter();
        foreach ($environments as &$env) {
            usort($env, $sorter);
        }

        return $environments;
    }
}
