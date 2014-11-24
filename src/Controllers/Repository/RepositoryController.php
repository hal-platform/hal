<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Repository;

use QL\Hal\Core\Entity\Deployment;
use QL\Hal\Core\Entity\Repository\DeploymentRepository;
use QL\Hal\Core\Entity\Repository\RepositoryRepository;
use QL\Hal\Services\PermissionsService;
use QL\Panthor\TemplateInterface;
use Slim\Http\Request;
use Slim\Http\Response;

class RepositoryController
{
    /**
     * @type TemplateInterface
     */
    private $template;

    /**
     * @type RepositoryRepository
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
     * @param TemplateInterface $template
     * @param RepositoryRepository $repoRepo
     * @param DeploymentRepository $deploymentRepo
     * @param PermissionsService $permissions
     */
    public function __construct(
        TemplateInterface $template,
        RepositoryRepository $repoRepo,
        DeploymentRepository $deploymentRepo,
        PermissionsService $permissions
    ) {
        $this->template = $template;
        $this->repoRepo = $repoRepo;
        $this->deploymentRepo = $deploymentRepo;
        $this->permissions = $permissions;
    }

    /**
     *  @param Request $request
     *  @param Response $response
     *  @param array $params
     *  @param callable $notFound
     */
    public function __invoke(Request $request, Response $response, array $params = [], callable $notFound = null)
    {
        if (!$repo = $this->repoRepo->find($params['id'])) {
            return $notFound();
        }

        $deployments = $this->deploymentRepo->findBy(['repository' => $repo], ['server' => 'ASC']);

        $rendered = $this->template->render([
            'repository' => $repo,
            'deployment_environments' => $this->sort($deployments),
            'deployment_count' => count($deployments),
            'permissions' => $this->permissions->repositoryPermissionPairs($repo->getKey())
        ]);

        $response->setBody($rendered);
    }

    /**
     * @param Deployment[] $deployments
     * @return array
     */
    private function sort(array $deployments)
    {
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

        return $environments;
    }
}
