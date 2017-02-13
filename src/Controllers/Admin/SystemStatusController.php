<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Admin;

use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Predis\Client as Predis;
use QL\Hal\Core\Entity\Server;
use QL\Hal\Core\Utility\SortingTrait;
use QL\MCP\Common\Time\Clock;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use QL\Panthor\Utility\Json;

class SystemStatusController implements ControllerInterface
{
    use SortingTrait;

    const DOCKER_REDIS_KEY = 'agent-status:docker';
    const SERVERS_REDIS_KEY = 'agent-status:server';

    /**
     * @var TemplateInterface
     */
    private $template;

    /**
     * @var EntityRepository
     */
    private $serverRepo;

    /**
     * @var Predis
     */
    private $predis;

    /**
     * @var Json
     */
    private $json;

    /**
     * @var Clock
     */
    private $clock;

    /**
     * @var callable
     */
    private $notFound;

    /**
     * @var array
     */
    private $agents;
    private $parameters;

    /**
     * @param TemplateInterface $template
     * @param EntityManagerInterface $em
     * @param Predis $predis
     * @param Json $json
     * @param Clock $clock
     * @param callable $notFound
     *
     * @param array $agents
     * @param array $parameters
     */
    public function __construct(
        TemplateInterface $template,
        EntityManagerInterface $em,
        Predis $predis,
        Json $json,
        Clock $clock,
        callable $notFound,
        array $agents,
        array $parameters
    ) {
        $this->template = $template;
        $this->serverRepo = $em->getRepository(Server::CLASS);

        $this->json = $json;
        $this->clock = $clock;
        $this->predis = $predis;
        $this->notFound = $notFound;

        $this->agents = $agents;
        $this->parameters = $parameters;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        if (!$agent = $this->getAgentName()) {
            return call_user_func($this->notFound);
        }

        $system = $this->parseLatestDocker($agent);
        $connections = $this->parseLatestServerConnections($agent);

        $context = [
            'system' => $system,
            'connections' => $connections,
            'selected_agent' => $agent,
            'agents' => $this->agents
        ];

        $this->template->render($context);
    }

    /**
     * @return string|null
     */
    private function getAgentName()
    {
        if (!$this->agents) {
            return null;
        }

        if (!isset($this->parameters['agent'])) {
            return reset($this->agents);
        }

        foreach ($this->agents as $agent) {
            if ($agent === $this->parameters['agent']) {
                return $agent;
            }
        }

        return null;
    }

    /**
     * @param string $agent
     *
     * @return array|null
     */
    private function parseLatestDocker($agent)
    {
        if (!$docker = $this->getLatestStatusForAgent(self::DOCKER_REDIS_KEY, $agent)) {
            return null;
        }

        $time = isset($docker['generated']) ? $this->clock->fromString($docker['generated']) : null;

        return [
            'agent' => isset($docker['agent']) ? $docker['agent'] : '',
            'builder' => isset($docker['builder']) ? $docker['builder'] : '',
            'docker' => isset($docker['docker']) ? $docker['docker'] : '',
            'generated' => $time
        ];
    }

    /**
     * @param string $agent
     *
     * @return array|null
     */
    private function parseLatestServerConnections($agent)
    {
        if (!$connections = $this->getLatestStatusForAgent(self::SERVERS_REDIS_KEY, $agent)) {
            return null;
        }

        $serversByEnvironment = $this->aggregateServers();
        $failures = [];

        $time = isset($connections['generated']) ? $this->clock->fromString($connections['generated']) : null;
        $connections = isset($connections['servers']) ? $connections['servers'] : [];

        foreach ($serversByEnvironment as &$servers) {
            foreach ($servers as &$server) {

                if (isset($connections[$server->id()])) {
                    $status = $connections[$server->id()];

                    if (!$status['status']) {
                        $failures[] = [
                            'server' => $server,
                            'resolved' => $status['server'],
                            'status' => $status['status'],
                            'detail' => isset($status['detail']) ? $status['detail'] : ''
                        ];
                    }

                    $server = [
                        'server' => $server,
                        'resolved' => $status['server'],
                        'status' => $status['status'],
                        'detail' => isset($status['detail']) ? $status['detail'] : ''
                    ];
                } else {
                    $server = [
                        'server' => $server,
                        'status' => 'unknown',
                        'detail' => 'No status found.'
                    ];
                }
            }
        }

        return [
            'failures' => $failures,
            'servers' => $serversByEnvironment,
            'generated' => $time
        ];
    }

    /**
     * @param string $list
     * @param string $agent
     *
     * @return array|null
     */
    private function getLatestStatusForAgent($list, $agent)
    {
        for ($i = 0; $i <= 20; $i++) {
            $data = $this->predis->lindex($list, $i);

            if ($data === null) {
                return null;
            }

            $health = $this->json->decode($data);
            if (isset($health['generated_by']) && stripos($health['generated_by'], $agent) !== false) {
                return $health;
            }
        }
    }

    /**
     * @return array
     */
    private function aggregateServers()
    {
        $servers = $this->serverRepo->findBy(['type' => 'rsync']);
        usort($servers, $this->serverSorter());

        $keys = array_keys($this->sortingHelperEnvironmentOrder);
        $serverByEnvironment = array_fill_keys($keys, []);

        foreach ($servers as $server) {
            $name = $server->environment()->name();

            if (array_key_exists($name, $serverByEnvironment)) {
                $serverByEnvironment[$name][] = $server;
            }
        }

        return array_filter($serverByEnvironment, function ($servers) {
            return count($servers) > 0;
        });
    }
}
