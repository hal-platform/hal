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
use QL\Panthor\TemplateInterface;
use QL\Panthor\Utility\Url;
use Slim\Http\Request;
use Slim\Http\Response;

class AddDeploymentController
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
     * @param TemplateInterface $template
     * @param ServerRepository $serverRepo
     * @param RepositoryRepository $repoRepo
     * @param Url $url
     */
    public function __construct(
        TemplateInterface $template,
        ServerRepository $serverRepo,
        RepositoryRepository $repoRepo,
        Url $url
    ) {
        $this->template = $template;
        $this->serverRepo = $serverRepo;
        $this->repoRepo = $repoRepo;

        $this->url = $url;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $params
     * @param callable $notFound
     */
    public function __invoke(Request $request, Response $response, array $params = [], callable $notFound = null)
    {
        if (!$repo = $this->repoRepo->find($params['repository'])) {
            return $notFound();
        }

        $servers = $this->serverRepo->findAll();

        // If no servers, throw back to repo page
        if (!$servers) {
            $this->url->redirectFor('repository', ['repository' => $params['repository']]);
        }

        $rendered = $this->template->render([
            'form' => [
                'server' => $request->post('server'),
                'path' => $request->post('path'),
                'url' => $request->post('url')
            ],

            'servers_by_env' => $this->environmentalizeServers($servers),
            'repository' => $repo
        ]);

        $response->setBody($rendered);
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
