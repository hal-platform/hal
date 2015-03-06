<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Server;

use QL\Hal\Core\Repository\ServerRepository;
use QL\Hal\Core\Entity\Server;
use QL\Hal\Helpers\SortingHelperTrait;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use Slim\Http\Request;
use Slim\Http\Response;

class ServersController implements ControllerInterface
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
     * @type Response
     */
    private $response;

    /**
     * @param TemplateInterface $template
     * @param ServerRepository $serverRepo
     * @param Response $response
     */
    public function __construct(TemplateInterface $template, ServerRepository $serverRepo, Response $response)
    {
        $this->template = $template;
        $this->serverRepo = $serverRepo;
        $this->response = $response;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        $servers = $this->serverRepo->findBy([], ['name' => 'ASC']);

        $rendered = $this->template->render([
            'server_environments' => $this->sort($servers),
            'server_count' => count($servers)
        ]);

        $this->response->setBody($rendered);
    }

    /**
     * @param Server[] $servers
     * @return array
     */
    private function sort(array $servers)
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

        return $environments;
    }
}
