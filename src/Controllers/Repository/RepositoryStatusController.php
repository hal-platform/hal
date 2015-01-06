<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Repository;

use Doctrine\ORM\EntityManager;
use QL\Hal\Core\Entity\Build;
use QL\Hal\Core\Entity\Deployment;
use QL\Hal\Core\Entity\Repository;
use QL\Hal\Core\Entity\Repository\BuildRepository;
use QL\Hal\Core\Entity\Repository\DeploymentRepository;
use QL\Hal\Core\Entity\Repository\PushRepository;
use QL\Hal\Core\Entity\Repository\RepositoryRepository;
use QL\Hal\Helpers\SortingHelperTrait;
use QL\Panthor\TemplateInterface;
use Slim\Http\Request;
use Slim\Http\Response;

class RepositoryStatusController
{
    use SortingHelperTrait;

    /**
     * @type TemplateInterface
     */
    private $template;

    /**
     * @type EntityManager
     */
    private $em;

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
     * @param TemplateInterface $template
     * @param EntityManager $em
     * @param RepositoryRepository $repoRepo
     * @param BuildRepository $buildRepo
     * @param DeploymentRepository $deploymentRepo
     * @param PushRepository $pushRepo
     */
    public function __construct(
        TemplateInterface $template,
        EntityManager $em,
        RepositoryRepository $repoRepo,
        BuildRepository $buildRepo,
        DeploymentRepository $deploymentRepo,
        PushRepository $pushRepo
    ) {
        $this->template = $template;
        $this->em = $em;
        $this->repoRepo = $repoRepo;
        $this->buildRepo = $buildRepo;
        $this->deploymentRepo = $deploymentRepo;
        $this->pushRepo = $pushRepo;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $params
     * @param callable $notFound
     * @return void|mixed
     */
    public function __invoke(Request $request, Response $response, array $params = [], callable $notFound = null)
    {
        if (!$repo = $this->repoRepo->find($params['id'])) {
            return call_user_func($notFound);
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

        $response->setBody($rendered);
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
