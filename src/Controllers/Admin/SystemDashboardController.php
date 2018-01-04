<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Admin;

use Doctrine\ORM\EntityManagerInterface;
use Hal\UI\Controllers\TemplatedControllerTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\MCP\Common\Time\Clock;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use QL\Panthor\Utility\JSON;

class SystemDashboardController implements ControllerInterface
{
    use TemplatedControllerTrait;

    const DOCKER_REDIS_KEY = 'agent-status:docker';
    const SERVERS_REDIS_KEY = 'agent-status:server';

    /**
     * @var TemplateInterface
     */
    private $template;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var JSON
     */
    private $json;

    /**
     * @var Clock
     */
    private $clock;

    /**
     * @var string
     */
    private $encryptionKey;

    /**
     * @var string
     */
    private $sessionEncryptionKey;

    /**
     * @var string
     */
    private $halDeploymentFile;

    /**
     * @param TemplateInterface $template
     * @param EntityManagerInterface $em
     * @param JSON $json
     * @param Clock $clock
     * @param string $encryptionKey
     * @param string $sessionEncryptionKey
     * @param string $halDeploymentFile
     */
    public function __construct(
        TemplateInterface $template,
        EntityManagerInterface $em,
        JSON $json,
        Clock $clock,
        $encryptionKey,
        $sessionEncryptionKey,
        $halDeploymentFile
    ) {
        $this->template = $template;
        $this->em = $em;

        $this->json = $json;
        $this->clock = $clock;

        $this->encryptionKey = $encryptionKey;
        $this->sessionEncryptionKey = $sessionEncryptionKey;
        $this->halDeploymentFile = $halDeploymentFile;
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        // $agent = 'tbd';
        // $system = $this->parseLatestDocker($agent);
        // $connections = $this->parseLatestServerConnections($agent);

        # get hal push file if possible.
        $deploymentFile = file_exists($this->halDeploymentFile) ? file_get_contents($this->halDeploymentFile) : '';

        return $this->withTemplate($request, $response, $this->template, [
            // 'system' => $system,
            // 'connections' => $connections,
            'server_name' => gethostname(),
            'encryption_key' => $this->encryptionKey,
            'session_encryption_key' => $this->sessionEncryptionKey,
            'release_file' => $deploymentFile
        ]);
    }

    // /**
    //  * @param string $agent
    //  *
    //  * @return array|null
    //  */
    // private function parseLatestDocker($agent)
    // {
    //     if (!$docker = $this->getLatestStatusForAgent(self::DOCKER_REDIS_KEY, $agent)) {
    //         return null;
    //     }

    //     $time = isset($docker['generated']) ? $this->clock->fromString($docker['generated']) : null;

    //     return [
    //         'agent' => isset($docker['agent']) ? $docker['agent'] : '',
    //         'builder' => isset($docker['builder']) ? $docker['builder'] : '',
    //         'docker' => isset($docker['docker']) ? $docker['docker'] : '',
    //         'generated' => $time
    //     ];
    // }

    // /**
    //  * @param string $list
    //  * @param string $agent
    //  *
    //  * @return array|null
    //  */
    // private function getLatestStatusForAgent($list, $agent)
    // {
    //     for ($i = 0; $i <= 20; $i++) {
    //         $data = $this->predis->lindex($list, $i);

    //         if ($data === null) {
    //             return null;
    //         }

    //         $health = $this->json->decode($data);
    //         if (isset($health['generated_by']) && stripos($health['generated_by'], $agent) !== false) {
    //             return $health;
    //         }
    //     }
    // }
}
