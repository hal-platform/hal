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
use QL\Hal\Core\Entity\Server;
use QL\Hal\Core\Utility\SortingTrait;
use QL\Panthor\Slim\NotFound;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use QL\Panthor\Utility\Url;
use Slim\Http\Request;

class AddDeploymentController implements ControllerInterface
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

    /**
     * @type Url
     */
    private $url;

    /**
     * @type Request
     */
    private $request;

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
     * @param Url $url
     * @param Request $request
     * @param NotFound $notFound
     * @param array $parameters
     */
    public function __construct(
        TemplateInterface $template,
        EntityManagerInterface $em,
        Url $url,
        Request $request,
        NotFound $notFound,
        array $parameters
    ) {
        $this->template = $template;
        $this->serverRepo = $em->getRepository(Server::CLASS);
        $this->applicationRepo = $em->getRepository(Application::CLASS);
        $this->url = $url;

        $this->request = $request;
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

        $servers = $this->serverRepo->findAll();

        // If no servers, throw back to repo page
        if (!$servers) {
            $this->url->redirectFor('repository', ['repository' => $this->parameters['repository']]);
        }

        $this->template->render([
            'form' => [
                'server' => $this->request->post('server'),
                'path' => $this->request->post('path'),
                'url' => $this->request->post('url'),
                'eb_environment' => $this->request->post('eb_environment')
            ],

            'servers_by_env' => $this->environmentalizeServers($servers),
            'application' => $application
        ]);
    }

    /**
     * @param Server[] $servers
     * @return array
     */
    private function environmentalizeServers(array $servers)
    {
        $environments = [
            'dev' => [],
            'test' => [],
            'beta' => [],
            'prod' => []
        ];

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
