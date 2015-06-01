<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use QL\Hal\Core\Entity\Application;
use QL\Hal\Core\Entity\Deployment;
use QL\Hal\Core\Repository\DeploymentRepository;
use QL\Hal\Helpers\SortingHelperTrait;
use QL\Hal\Services\ElasticBeanstalkService;
use QL\Kraken\Core\Entity\Application as KrakenApplication;
use QL\Panthor\Slim\NotFound;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;

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
    private $applicationRepo;

    /**
     * @type DeploymentRepository
     */
    private $deploymentRepo;

    /**
     * @type EntityRepository
     */
    private $krakenRepo;

    /**
     * @type ElasticBeanstalkService
     */
    private $ebService;

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
     * @param ElasticBeanstalkService $ebService
     * @param NotFound $notFound
     * @param array $parameters
     */
    public function __construct(
        TemplateInterface $template,
        EntityManagerInterface $em,
        ElasticBeanstalkService $ebService,
        NotFound $notFound,
        array $parameters
    ) {
        $this->template = $template;

        $this->applicationRepo = $em->getRepository(Application::CLASS);
        $this->deploymentRepo = $em->getRepository(Deployment::CLASS);
        $this->krakenRepo = $em->getRepository(KrakenApplication::CLASS);

        $this->ebService = $ebService;

        $this->notFound = $notFound;
        $this->parameters = $parameters;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        if (!$application = $this->applicationRepo->find($this->parameters['id'])) {
            return call_user_func($this->notFound);
        }

        $krakenApp = $this->krakenRepo->findOneBy(['halApplication' => $application]);

        $deployments = $this->deploymentRepo->findBy(['application' => $application]);
        $environmentalized = $this->environmentalizeDeployments($deployments);
        $ebEnvironments = $this->ebService->getEnvironmentsByDeployments($deployments);
        $deploymentCount = count($deployments);

        foreach ($environmentalized as $env => &$deployments) {
            foreach ($deployments as &$deployment) {
                $ebEnv = '';
                if (isset($ebEnvironments[$deployment->id()])) {
                    $ebEnv = $ebEnvironments[$deployment->id()];
                }

                $deployment = [
                    'deployment' => $deployment,
                    'eb_environment' => $ebEnv
                ];
            }
        }

        $this->template->render([
            'repository' => $application,
            'deployment_environments' => $environmentalized,
            'deployment_count' => $deploymentCount,
            'kraken' => $krakenApp
        ]);
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
            $env = $deployment->server()->environment()->name();

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
