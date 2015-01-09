<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Repository;

use QL\Hal\Core\Entity\Build;
use QL\Hal\Core\Entity\Deployment;
use QL\Hal\Core\Entity\Repository;
use QL\Hal\Core\Entity\Repository\BuildRepository;
use QL\Hal\Core\Entity\Repository\DeploymentRepository;
use QL\Hal\Core\Entity\Repository\PushRepository;
use QL\Hal\Core\Entity\Repository\RepositoryRepository;
use QL\Hal\Helpers\SortingHelperTrait;
use QL\Hal\Slim\NotFound;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use Slim\Http\Response;

class RepositoryStatusController implements ControllerInterface
{
    use SortingHelperTrait;

    /**
     * @type TemplateInterface
     */
    private $template;

    /**
     * @type RepositoryRepository
     */
    private $repoRepo;

    /**
     * @type BuildRepository
     */
    private $buildRepo;

    /**
     * @type DeploymentRepository
     */
    private $deploymentRepo;

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
     * @param RepositoryRepository $repoRepo
     * @param BuildRepository $buildRepo
     * @param DeploymentRepository $deploymentRepo
     * @param PushRepository $pushRepo
     */
    public function __construct(
        TemplateInterface $template,
        RepositoryRepository $repoRepo,
        BuildRepository $buildRepo,
        DeploymentRepository $deploymentRepo,
        PushRepository $pushRepo,
        Response $response,
        NotFound $notFound,
        array $parameters
    ) {
        $this->template = $template;
        $this->repoRepo = $repoRepo;
        $this->buildRepo = $buildRepo;
        $this->deploymentRepo = $deploymentRepo;
        $this->pushRepo = $pushRepo;

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

        $builds = $this->buildRepo->findBy(['repository' => $repo], ['created' => 'DESC'], 10);

        $deployments = $this->deploymentRepo->findBy(['repository' => $repo]);
        $environments = $this->environmentalizeDeployments($deployments);

        $dupes = [];
        $hasDuplicates = false;

        foreach ($environments as &$deployments) {
            foreach ($deployments as &$deployment) {

                $key = $deployment->getServer()->getId();
                if (isset($dupes[$key])) {
                    $hasDuplicates = true;
                }
                $dupes[$key] = true;

                $deployment = [
                    'deploy' => $deployment,
                    'latest' => $this->pushRepo->getMostRecentByDeployment($deployment),
                    'success' =>$this->pushRepo->getMostRecentSuccessByDeployment($deployment)
                ];
            }
        }

        $rendered = $this->template->render([
            'repo' => $repo,
            'builds' => $builds,
            'environments' => $environments,
            'duplicates' => $hasDuplicates
        ]);

        $this->response->setBody($rendered);
    }

    /**
     * @param Deployment[] $deployments
     * @return array
     */
    private function environmentalizeDeployments(array $deployments)
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

        $sorter = $this->deploymentSorter();
        foreach ($environments as &$env) {
            usort($env, $sorter);
        }

        return $environments;
    }
}
