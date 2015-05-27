<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use QL\Hal\Core\Entity\Build;
use QL\Hal\Core\Entity\Deployment;
use QL\Hal\Core\Entity\Environment;
use QL\Hal\Core\Entity\Repository;
use QL\Hal\Core\Entity\Push;
use QL\Hal\Core\Repository\BuildRepository;
use QL\Hal\Core\Repository\DeploymentRepository;
use QL\Hal\Core\Repository\EnvironmentRepository;
use QL\Hal\Core\Repository\PushRepository;
use QL\Hal\Helpers\SortingHelperTrait;
use QL\Kraken\Core\Entity\Application as KrakenApplication;
use QL\Panthor\Slim\NotFound;
use QL\Hal\Services\StickyEnvironmentService;
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
     * @type EntityRepository
     */
    private $repoRepo;
    private $krakenRepo;

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
     * @type EnvironmentRepository
     */
    private $envRepo;

    /**
     * @type StickyEnvironmentService
     */
    private $stickyService;

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
     * @param EntityMangerInterface $em
     *
     * @param StickyEnvironmentService $stickyService
     * @param Response $response
     * @param NotFound $notFound
     * @param array $parameters
     *
     */
    public function __construct(
        TemplateInterface $template,
        EntityManagerInterface $em,

        StickyEnvironmentService $stickyService,
        Response $response,
        NotFound $notFound,
        array $parameters
    ) {
        $this->template = $template;

        $this->repoRepo = $em->getRepository(Repository::CLASS);
        $this->deploymentRepo = $em->getRepository(Deployment::CLASS);
        $this->envRepo = $em->getRepository(Environment::CLASS);

        $this->buildRepo = $em->getRepository(Build::CLASS);
        $this->pushRepo = $em->getRepository(Push::CLASS);

        $this->krakenRepo = $em->getRepository(KrakenApplication::CLASS);

        $this->stickyService = $stickyService;
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

        $selected = $this->stickyService->get($repo->getId());

        $environments = $this->envRepo->getBuildableEnvironmentsByRepository($repo);
        // if empty, throw them a bone with "test"
        if (!$environments) {
            $environments = $this->envRepo->findBy(['key' => 'test']);
        }

        $selectedEnvironment = $this->findSelectedEnvironment($environments, $selected);

        $deployments = [];
        if ($selectedEnvironment) {
            $deployments = $this->deploymentRepo->getDeploymentsByRepositoryEnvironment($repo, $selectedEnvironment);
        }

        usort($deployments, $this->deploymentSorter());

        foreach ($deployments as &$deployment) {
            $deployment = [
                'deploy' => $deployment,
                'latest' => $this->pushRepo->getMostRecentByDeployment($deployment)
            ];
        }

        $builds = $this->buildRepo->findBy(['repository' => $repo, 'environment' => $selectedEnvironment], ['created' => 'DESC'], 10);

        $krakenApp = $this->krakenRepo->findOneBy(['halApplication' => $repo]);

        $rendered = $this->template->render([
            'repo' => $repo,
            'builds' => $builds,
            'environments' => $environments,
            'deployment_statuses' => $deployments,
            'selected_environment' => $selectedEnvironment,

            'kraken' => $krakenApp
        ]);

        $this->response->setBody($rendered);
    }

    /**
     * @param Environment[] $environments
     * @param string $selected
     *
     *
     * @return Environment|null
     */
    private function findSelectedEnvironment($environments, $selected)
    {
        // list empty
        if (!$environments) {
            return null;
        }

        // Find the selected environment
        foreach ($environments as $environment) {
            if ($selected == $environment->getId()) {
                return $environment;
            }
        }

        // Not in the list? Just get the first
        return $environments[0];
    }
}
