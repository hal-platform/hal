<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Repository\Deployment;

use QL\Hal\Core\Entity\Repository\DeploymentRepository;
use QL\Hal\Core\Entity\Repository\EnvironmentRepository;
use QL\Hal\Core\Entity\Repository\RepositoryRepository;
use QL\Hal\Core\Entity\Repository\ServerRepository;
use QL\Hal\Core\Entity\Environment;
use QL\Hal\Core\Entity\Server;
use QL\Hal\Helpers\SortingHelperTrait;
use QL\Panthor\TemplateInterface;
use Slim\Http\Request;
use Slim\Http\Response;

class DeploymentsController
{
    use SortingHelperTrait;

    /**
     * @type TemplateInterface
     */
    private $template;

    /**
     * @type EnvironmentRepository
     */
    private $environmentRepo;

    /**
     * @type ServerRepository
     */
    private $serverRepo;

    /**
     * @type RepositoryRepository
     */
    private $repoRepo;

    /**
     * @type DeploymentRepository
     */
    private $deploymentRepo;

    /**
     * @param TemplateInterface $template
     * @param EnvironmentRepository $environmentRepo
     * @param ServerRepository $serverRepo
     * @param RepositoryRepository $repoRepo
     * @param DeploymentRepository $deploymentRepo
     */
    public function __construct(
        TemplateInterface $template,
        EnvironmentRepository $environmentRepo,
        ServerRepository $serverRepo,
        RepositoryRepository $repoRepo,
        DeploymentRepository $deploymentRepo
    ) {
        $this->template = $template;
        $this->environmentRepo = $environmentRepo;
        $this->serverRepo = $serverRepo;
        $this->repoRepo = $repoRepo;
        $this->deploymentRepo = $deploymentRepo;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $params
     * @param callable $notFound
     */
    public function __invoke(Request $request, Response $response, array $params = [], callable $notFound = null)
    {
        if (!$repo = $this->repoRepo->find($params['repository'])) {
            return $notFound();
        }

        // Get and sort deployments
        $deployments = $this->deploymentRepo->findBy(['repository' => $repo]);
        $sorter = $this->deploymentSorter();
        usort($deployments, $sorter);

        $environments = $this->environmentRepo->findBy([], ['order' => 'ASC']);

        $rendered = $this->template->render([
            'environments' => $environments,
            'servers_by_env' => $this->environmentalizeServers($environments, $this->serverRepo->findAll()),
            'deployments' => $deployments,
            'repository' => $repo
        ]);

        $response->setBody($rendered);
    }

    /**
     * @param Environment[] $environments
     * @param Server[] $servers
     *
     * @return array
     */
    private function environmentalizeServers(array $environments, array $servers)
    {
        $env = [];
        foreach ($environments as $environment) {
            $env[$environment->getKey()] = [];
        }

        $environments = $env;

        foreach ($servers as $server) {
            $env = $server->getEnvironment()->getKey();

            if (!array_key_exists($env, $environments)) {
                $environments[$env] = [];
            }

            $environments[$env][] = $server;
        }

        $sorter = $this->serverSorter();
        foreach ($environments as &$env) {
            usort($env, $sorter);
        }

        foreach ($environments as $key => $servers) {
            if (count($servers) === 0) {
                unset($environments[$key]);
            }
        }

        return $environments;
    }
}
