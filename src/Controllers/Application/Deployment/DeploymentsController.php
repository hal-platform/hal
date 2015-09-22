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
use QL\Hal\Core\Type\EnumType\ServerEnum;
use QL\Hal\Core\Utility\SortingTrait;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;

class DeploymentsController implements ControllerInterface
{
    use SortingTrait;

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
     * @type Application
     */
    private $application;

    /**
     * @param TemplateInterface $template
     * @param EntityManagerInterface $em
     * @param Application $application
     */
    public function __construct(
        TemplateInterface $template,
        EntityManagerInterface $em,
        Application $application
    ) {
        $this->template = $template;
        $this->environmentRepo = $em->getRepository(Environment::CLASS);
        $this->serverRepo = $em->getRepository(Server::CLASS);
        $this->deploymentRepo = $em->getRepository(Deployment::CLASS);

        $this->application = $application;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        $environments = $this->environmentRepo->getAllEnvironmentsSorted();

        $assoc = [];
        foreach ($environments as $env) {
            $assoc[$env->name()] = $env;
        }

        $this->template->render([
            'servers_by_env' => $this->environmentalizeServers($environments),
            'deployment_by_env' => $this->environmentalizeDeployments($environments),

            'environments' => $assoc,
            'application' => $this->application
        ]);
    }

    /**
     * @param Environment[] $environments
     *
     * @return array
     */
    private function environmentalizeDeployments(array $environments)
    {
        $deployments = $this->deploymentRepo->findBy(['application' => $this->application]);
        $sorter = $this->deploymentSorter();
        usort($deployments, $sorter);

        $env = [];
        foreach ($environments as $environment) {
            $env[$environment->name()] = [];
        }

        foreach ($deployments as $deployment) {
            $name = $deployment->server()->environment()->name();
            $env[$name][] = $deployment;
        }

        return $env;
    }

    /**
     * NOTE: non-rsync servers are filtered out of this list.
     *
     * @param Environment[] $environments
     *
     * @return array
     */
    private function environmentalizeServers(array $environments)
    {
        $servers = $this->serverRepo->findAll();

        $env = [];
        foreach ($environments as $environment) {
            $env[$environment->name()] = [];
        }

        $environments = $env;

        foreach ($servers as $server) {
            $env = $server->environment()->name();

            if ($server->type() !== ServerEnum::TYPE_RSYNC) {
                continue;
            }

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
