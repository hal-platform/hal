<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Push;

use QL\Hal\Core\Entity\Repository\DeploymentRepository;
use QL\Hal\Core\Entity\Repository\PushRepository;
use QL\Hal\Core\Entity\Repository\RepositoryRepository;
use QL\Hal\Slim\NotFound;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use Slim\Http\Response;

class RollbackController implements ControllerInterface
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
    private $serverRepo;

    /**
     * @type PushRepository
     */
    private $pushRepo;

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
     * @param RepositoryRepository $repoRepository
     * @param ServerRepository $serverRepository
     * @param PushRepository $pushRepository
     * @param Response $response
     * @param NotFound $notFound
     * @param array $parameters
     */
    public function __construct(
        TemplateInterface $template,
        RepositoryRepository $repoRepository,
        DeploymentRepository $deploymentRepository,
        PushRepository $pushRepository,
        Response $response,
        NotFound $notFound,
        array $parameters
    ) {
        $this->template = $template;
        $this->repoRepo = $repoRepository;
        $this->deploymentRepository = $deploymentRepository;
        $this->pushRepo = $pushRepository;

        $this->response = $response;
        $this->notFound = $notFound;
        $this->parameters = $parameters;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        $repo = $this->repoRepo->find($this->parameters['id']);
        $deployment = $this->deploymentRepository->findOneBy([
            'id' => $this->parameters['deployment'],
            'repository' => $repo
        ]);

        if (!$repo || !$deployment) {
            return call_user_func($this->notFound);
        }

        $pushes = $this->pushRepo->getAvailableRollbacksByDeployment($deployment, 50);

        $rendered = $this->template->render([
            'repo' => $repo,
            'deployment' => $deployment,
            'pushes' => $pushes
        ]);

        $this->response->setBody($rendered);
    }
}
