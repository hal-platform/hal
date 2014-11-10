<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Repository;

use QL\Hal\Core\Entity\Repository\DeploymentRepository;
use QL\Hal\Core\Entity\Repository\RepositoryRepository;
use QL\Hal\Services\PermissionsService;
use Slim\Http\Request;
use Slim\Http\Response;
use Twig_Template;

class RepositoryController
{
    /**
     *  @var Twig_Template
     */
    private $template;

    /**
     *  @var RepositoryRepository
     */
    private $repoRepo;

    /**
     *  @var DeploymentRepository
     */
    private $deploymentRepo;

    /**
     * @var PermissionsService
     */
    private $permissions;

    /**
     * @param Twig_Template $template
     * @param RepositoryRepository $repoRepo
     * @param DeploymentRepository $deploymentRepo
     * @param PermissionsService $permissions
     */
    public function __construct(
        Twig_Template $template,
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

        $rendered = $this->template->render([
            'repository' => $repo,
            'deployments' => $this->deploymentRepo->findBy(['repository' => $repo], ['server' => 'ASC']),
            'permissions' => $this->permissions->repositoryPermissionPairs($repo->getKey())
        ]);

        $response->body($rendered);
    }
}
