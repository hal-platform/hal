<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Application\Deployment;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use QL\Hal\Core\Entity\Application;
use QL\Hal\Core\Entity\Deployment;
use QL\Hal\Core\Entity\Environment;
use QL\Hal\Core\Entity\Server;
use QL\Hal\Core\Repository\EnvironmentRepository;
use QL\Panthor\Slim\NotFound;
use QL\Hal\Helpers\SortingHelperTrait;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;

class DeploymentsController implements ControllerInterface
{
    use SortingHelperTrait;

    /**
     * @type TemplateInterface
     */
    private $template;

    /**
     * @type EntityRepository
     */
    private $serverRepo;
    private $applicationRepo;
    private $deploymentRepo;

    /**
     * @type EnvironmentRepository
     */
    private $environmentRepo;

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
     * @param EntityManagerInterface $em
     * @param NotFound $notFound
     * @param array $parameters
     */
    public function __construct(
        TemplateInterface $template,
        EntityManagerInterface $em,
        NotFound $notFound,
        array $parameters
    ) {
        $this->template = $template;
        $this->environmentRepo = $em->getRepository(Environment::CLASS);
        $this->serverRepo = $em->getRepository(Server::CLASS);
        $this->applicationRepo = $em->getRepository(Application::CLASS);
        $this->deploymentRepo = $em->getRepository(Deployment::CLASS);

        $this->notFound = $notFound;
        $this->parameters = $parameters;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        if (!$application = $this->applicationRepo->find($this->parameters['repository'])) {
            return call_user_func($this->notFound);
        }

        // Get and sort deployments
        $deployments = $this->deploymentRepo->findBy(['application' => $application]);
        $sorter = $this->deploymentSorter();
        usort($deployments, $sorter);

        $environments = $this->environmentRepo->getAllEnvironmentsSorted();

        $this->template->render([
            'environments' => $environments,
            'servers_by_env' => $this->environmentalizeServers($environments, $this->serverRepo->findAll()),
            'deployments' => $deployments,
            'application' => $application
        ]);
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
            $env[$environment->name()] = [];
        }

        $environments = $env;

        foreach ($servers as $server) {
            $env = $server->environment()->name();

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
