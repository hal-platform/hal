<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Server;

use QL\Hal\Core\Entity\Repository\ServerRepository;
use QL\Hal\Core\Entity\Server;
use Slim\Http\Request;
use Slim\Http\Response;
use Twig_Template;

class ServersController
{
    /**
     *  @var Twig_Template
     */
    private $template;

    /**
     *  @var ServerRepository
     */
    private $serverRepo;

    /**
     *  @param Twig_Template $template
     *  @param ServerRepository $serverRepo
     */
    public function __construct(Twig_Template $template, ServerRepository $serverRepo)
    {
        $this->template = $template;
        $this->serverRepo = $serverRepo;
    }

    /**
     *  @param Request $request
     *  @param Response $response
     *  @param array $params
     *  @param callable $notFound
     */
    public function __invoke(Request $request, Response $response, array $params = [], callable $notFound = null)
    {
        $servers = $this->serverRepo->findBy([], ['name' => 'ASC']);

        $rendered = $this->template->render([
            'server_environments' => $this->sortStandard($servers),
            'unknown_server_environments' => $this->sortUnknown($servers),
            'server_count' => count($servers)
        ]);

        $response->setBody($rendered);
    }

    /**
     * @param Server[] $servers
     * @return array
     */
    private function sortStandard(array $servers)
    {
        $environments = [
            'dev' => [],
            'test' => [],
            'beta' => [],
            'prod' => []
        ];

        foreach ($servers as $server) {
            $env = $server->getEnvironment()->getKey();

            if (in_array($env, ['dev', 'test', 'beta', 'prod'])) {
                $environments[$env][] = $server;
            }
        }

        return $environments;
    }

    /**
     * @param Server[] $servers
     * @return array
     */
    private function sortUnknown(array $servers)
    {
        $environments = [];

        foreach ($servers as $server) {
            $env = $server->getEnvironment()->getKey();

            if (in_array($env, ['dev', 'test', 'beta', 'prod'])) {
                continue;
            }

            if (!array_key_exists($env, $environments)) {
                $environments[$env] = [];
            }

            $environments[$env][] = $server;
        }

        return $environments;
    }
}
