<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Repository\Deployment;

use QL\Hal\Core\Entity\Server;
use QL\Hal\Core\Entity\Repository\RepositoryRepository;
use QL\Hal\Core\Entity\Repository\ServerRepository;
use QL\Hal\Helpers\SortingHelperTrait;
use QL\Hal\Slim\NotFound;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use QL\Panthor\Utility\Url;
use Slim\Http\Request;
use Slim\Http\Response;

class AddDeploymentController implements ControllerInterface
{
    use SortingHelperTrait;

    /**
     * @type TemplateInterface
     */
    private $template;

    /**
     * @type ServerRepository
     */
    private $serverRepo;

    /**
     * @type RepositoryRepository
     */
    private $repoRepo;

    /**
     * @type Url
     */
    private $url;

    /**
     * @type Request
     */
    private $request;

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
     * @param ServerRepository $serverRepo
     * @param RepositoryRepository $repoRepo
     * @param Url $url
     * @param Request $request
     * @param Response $response
     * @param NotFound $notFound
     * @param array $parameters
     */
    public function __construct(
        TemplateInterface $template,
        ServerRepository $serverRepo,
        RepositoryRepository $repoRepo,
        Url $url,
        Request $request,
        Response $response,
        NotFound $notFound,
        array $parameters
    ) {
        $this->template = $template;
        $this->serverRepo = $serverRepo;
        $this->repoRepo = $repoRepo;
        $this->url = $url;

        $this->request = $request;
        $this->response = $response;
        $this->notFound = $notFound;
        $this->parameters = $parameters;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        if (!$repo = $this->repoRepo->find($this->parameters['repository'])) {
            return call_user_func($this->notFound);
        }

        $servers = $this->serverRepo->findAll();

        // If no servers, throw back to repo page
        if (!$servers) {
            $this->url->redirectFor('repository', ['repository' => $this->parameters['repository']]);
        }

        $rendered = $this->template->render([
            'form' => [
                'server' => $this->request->post('server'),
                'path' => $this->request->post('path'),
                'url' => $this->request->post('url'),
                'ebs_environment' => $this->request->post('ebs_environment')
            ],

            'servers_by_env' => $this->environmentalizeServers($servers),
            'repository' => $repo
        ]);

        $this->response->setBody($rendered);
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
            $env = $server->getEnvironment()->getKey();

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
