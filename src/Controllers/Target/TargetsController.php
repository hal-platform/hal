<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Target;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Hal\UI\Controllers\TemplatedControllerTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Hal\Core\Entity\Application;
use QL\Hal\Core\Entity\Deployment;
use QL\Hal\Core\Entity\Environment;
use QL\Hal\Core\Entity\Server;
use QL\Hal\Core\Repository\EnvironmentRepository;
use QL\Hal\Core\Type\EnumType\ServerEnum;
use QL\Hal\Core\Utility\SortingTrait;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;

class TargetsController implements ControllerInterface
{
    use SortingTrait;
    use TemplatedControllerTrait;

    /**
     * @var TemplateInterface
     */
    private $template;

    /**
     * @var EntityRepository
     */
    private $serverRepo;
    private $applicationRepo;
    private $deploymentRepo;

    /**
     * @var EnvironmentRepository
     */
    private $environmentRepo;

    /**
     * @param TemplateInterface $template
     * @param EntityManagerInterface $em
     */
    public function __construct(TemplateInterface $template, EntityManagerInterface $em)
    {
        $this->template = $template;
        $this->environmentRepo = $em->getRepository(Environment::class);
        $this->serverRepo = $em->getRepository(Server::class);
        $this->deploymentRepo = $em->getRepository(Deployment::class);
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $application = $request->getAttribute(Application::class);

        $environments = [];
        foreach ($this->environmentRepo->getAllEnvironmentsSorted() as $env) {
            $environments[$env->name()] = $env;
        }

        return $this->withTemplate($request, $response, $this->template, [
            'environments' => $environments,
            'application' => $application,

            'servers_by_env' => $this->environmentalizeServers($environments),
            'targets_by_env' => $this->environmentalizeDeployments($application, $environments)
        ]);
    }

    /**
     * @param Application $application
     * @param Environment[] $environments
     *
     * @return array
     */
    private function environmentalizeDeployments(Application $application, array $environments)
    {
        $deployments = $this->deploymentRepo->findBy(['application' => $application]);
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
