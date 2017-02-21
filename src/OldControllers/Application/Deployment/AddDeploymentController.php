<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Application\Deployment;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use QL\Hal\Core\Entity\Application;
use QL\Hal\Core\Entity\Environment;
use QL\Hal\Core\Entity\Server;
use QL\Hal\Core\Repository\EnvironmentRepository;
use QL\Hal\Core\Utility\SortingTrait;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use QL\Panthor\Utility\Url;
use Slim\Http\Request;

class AddDeploymentController implements ControllerInterface
{
    use SortingTrait;

    /**
     * @var TemplateInterface
     */
    private $template;

    /**
     * @var EntityRepository
     */
    private $serverRepo;

    /**
     * @var EnvironmentRepository
     */
    private $environmentRepo;

    /**
     * @var Url
     */
    private $url;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var Application
     */
    private $application;

    /**
     * @param TemplateInterface $template
     * @param EntityManagerInterface $em
     * @param Url $url
     * @param Request $request
     * @param Application $application
     */
    public function __construct(
        TemplateInterface $template,
        EntityManagerInterface $em,
        Url $url,
        Request $request,
        Application $application
    ) {
        $this->template = $template;

        $this->serverRepo = $em->getRepository(Server::CLASS);
        $this->environmentRepo = $em->getRepository(Environment::CLASS);

        $this->url = $url;
        $this->request = $request;
        $this->application = $application;
    }

    /**
     * @inheritDoc
     */
    public function __invoke()
    {
        $environments = $this->environmentRepo->getAllEnvironmentsSorted();
        $serversByEnv = $this->environmentalizeServers($environments);

        // If no servers, throw back to repo page
        if (!$serversByEnv) {
            $this->url->redirectFor('application', ['application' => $this->application->id()]);
        }

        $this->template->render([
            'form' => $this->data(),

            'servers_by_env' => $serversByEnv,
            'application' => $this->application
        ]);
    }


    /**
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

    /**
     * @return array
     */
    private function data()
    {
        $form = [
            'server' => $this->request->post('server'),
            'path' => $this->request->post('path'),

            'cd_name' => $this->request->post('cd_name'),
            'cd_group' => $this->request->post('cd_group'),
            'cd_config' => $this->request->post('cd_config'),

            'eb_name' => $this->request->post('eb_name'),
            'eb_environment' => $this->request->post('eb_environment'),

            's3_bucket' => $this->request->post('s3_bucket'),
            's3_file' => $this->request->post('s3_file'),

            'url' => $this->request->post('url'),
        ];

        return $form;
    }
}
