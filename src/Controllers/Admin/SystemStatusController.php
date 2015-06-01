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
use QL\Hal\Helpers\SortingHelperTrait;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use QL\Panthor\Utility\Json;

class SystemStatusController implements ControllerInterface
{
    use SortingHelperTrait;

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
     * @param TemplateInterface $template
     * @param EntityManagerInterface $em
     * @param Predis $predis
     * @param Json $json
     */
    public function __construct(TemplateInterface $template, EntityManagerInterface $em, Predis $predis, Json $json)
    {
        $this->template = $template;
        $this->serverRepo = $em->getRepository(Server::CLASS);

        $this->json = $json;
        $this->predis = $predis;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {

        $system = $this->parseLatestDocker();
        $connections = $this->parseLatestServerConnections();

        $context = [
            'system' => $system,
            'connections' => $connections
        ];

        $this->template->render($context);
    }

    /**
     * @return array|null
     */
    public function parseLatestDocker()
    {
        if (!$latestDocker = $this->predis->lindex(self::DOCKER_REDIS_KEY, 0)) {
            return null;
        }
        $docker = $this->json->decode($latestDocker);
        $time = isset($docker['generated']) ? $this->buildTime($docker['generated']) : null;

        return [
            'agent' => isset($docker['agent']) ? $docker['agent'] : '',
            'builder' => isset($docker['builder']) ? $docker['builder'] : '',
            'docker' => isset($docker['docker']) ? $docker['docker'] : '',
            'generated' => $time
        ];
    }

    /**
     * @return array|null
     */
    public function parseLatestServerConnections()
    {
        if (!$latestConnections = $this->predis->lindex(self::SERVERS_REDIS_KEY, 0)) {
            return null;
        }

        $servers = $this->sort($this->serverRepo->findBy(['type' => 'rsync']));

        $connections = $this->json->decode($latestConnections);
        $time = isset($connections['generated']) ? $this->buildTime($connections['generated']) : null;
        $connections = isset($connections['servers']) ? $connections['servers'] : [];

        $parsed = [];

        foreach ($servers as $env => $servers) {
            foreach ($servers as $server) {

                if (!array_key_exists($env, $parsed)) {
                    $parsed[$env] = [];
                }

                if (isset($connections[$server->getId()])) {
                    $con = $connections[$server->getId()];
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
            $env = $server->getEnvironment()->name();

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
