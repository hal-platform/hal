<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Admin;

use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use MCP\DataType\Time\TimePoint;
use Predis\Client as Predis;
use QL\Hal\Core\Entity\Server;
use QL\Hal\Core\Utility\SortingTrait;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use QL\Panthor\Utility\Json;

class SystemStatusController implements ControllerInterface
{
    use SortingTrait;

    const DOCKER_REDIS_KEY = 'agent-status:docker';
    const SERVERS_REDIS_KEY = 'agent-status:server';

    /**
     * @type TemplateInterface
     */
    private $template;

    /**
     * @type EntityRepository
     */
    private $serverRepo;

    /**
     * @type Predis
     */
    private $predis;

    /**
     * @type Json
     */
    private $json;

    /**
     * @type callable
     */
    private $notFound;

    /**
     * @type array
     */
    private $agents;
    private $parameters;

    /**
     * @param TemplateInterface $template
     * @param EntityManagerInterface $em
     * @param Predis $predis
     * @param Json $json
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
        callable $notFound,
        array $agents,
        array $parameters
    ) {
        $this->template = $template;
        $this->serverRepo = $em->getRepository(Server::CLASS);

        $this->json = $json;
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

        $time = isset($docker['generated']) ? $this->buildTime($docker['generated']) : null;

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

        $servers = $this->sort($this->serverRepo->findBy(['type' => 'rsync']));

        $time = isset($connections['generated']) ? $this->buildTime($connections['generated']) : null;
        $connections = isset($connections['servers']) ? $connections['servers'] : [];

        $parsed = [];

        foreach ($servers as $env => $servers) {
            foreach ($servers as $server) {

                if (!array_key_exists($env, $parsed)) {
                    $parsed[$env] = [];
                }

                if (isset($connections[$server->id()])) {
                    $con = $connections[$server->id()];
                    $parsed[$env][] = [
                        'server' => $server,
                        'resolved' => $con['server'],
                        'status' => $con['status']
                    ];
                } else {
                    $parsed[$env][] = [
                        'server' => $server,
                        'status' => 'unknown'
                    ];
                }
            }
        }

        return [
            'servers' => $parsed,
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
     * @param string $time
     *
     * @return TimePoint|null
     */
    private function buildTime($value)
    {
        if (!$date = DateTime::createFromFormat(DateTime::ISO8601, $value)) {
            return null;
        }

        return new TimePoint(
            $date->format('Y'),
            $date->format('m'),
            $date->format('d'),
            $date->format('H'),
            $date->format('i'),
            $date->format('s'),
            'UTC'
        );
    }

    /**
     * @param Server[] $servers
     *
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

        return $environments;
    }
}
