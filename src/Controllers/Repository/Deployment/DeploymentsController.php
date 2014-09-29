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
use QL\Hal\Layout;
use Slim\Http\Request;
use Slim\Http\Response;
use Twig_Template;

class DeploymentsController
{
    /**
     *  @var Twig_Template
     */
    private $template;

    /**
     *  @var Layout
     */
    private $layout;

    /**
     *  @var EnvironmentRepository
     */
    private $environmentRepo;

    /**
     *  @var ServerRepository
     */
    private $serverRepo;

    /**
     *  @var RepositoryRepository
     */
    private $repoRepo;

    /**
     *  @var DeploymentRepository
     */
    private $deploymentRepo;

    /**
     *  @param Twig_Template $template
     *  @param Layout $layout
     *  @param EnvironmentRepository $environmentRepo
     *  @param ServerRepository $serverRepo
     *  @param RepositoryRepository $repoRepo
     *  @param DeploymentRepository $deploymentRepo
     */
    public function __construct(
        Twig_Template $template,
        Layout $layout,
        EnvironmentRepository $environmentRepo,
        ServerRepository $serverRepo,
        RepositoryRepository $repoRepo,
        DeploymentRepository $deploymentRepo
    ) {
        $this->template = $template;
        $this->layout = $layout;
        $this->environmentRepo = $environmentRepo;
        $this->serverRepo = $serverRepo;
        $this->repoRepo = $repoRepo;
        $this->deploymentRepo = $deploymentRepo;
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

        $rendered = $this->layout->render($this->template, [
            'environments' => $this->environmentRepo->findBy([], ['order' => 'ASC']),
            'servers' => $this->serverRepo->findBy([], ['name' => 'ASC']),
            'repository' => $repo,
            'deployments' => $this->deploymentRepo->findBy(['repository' => $repo], ['server' => 'ASC'])
        ]);

        $response->body($rendered);
    }
}
