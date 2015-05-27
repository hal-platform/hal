<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Admin;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use MCP\Cache\CachingTrait;
use QL\Hal\Core\Entity\Server;
use QL\Panthor\ControllerInterface;
use Slim\Http\Response;

class ServerStatusHandler implements ControllerInterface
{
    use CachingTrait;

    const CACHE_SERVER_STATUS = 'server.status.%s';
    const VALUE_DOWN = 'down';
    const VALUE_UP = 'up';
    const VALUE_MAYBE = 'unknown';

    /**
     * @type Response
     */
    private $response;

    /**
     * @type EntityRepository
     */
    private $serverRepo;

    /**
     * @type string
     */
    private $cliUser;

    /**
     * @type array
     */
    private $parameters;

    /**
     * @param Response $response
     * @param EntityManagerInterface $em
     * @param array $routeParameters
     * @param string $cliUser
     */
    public function __construct(
        Response $response,
        EntityManagerInterface $em,
        array $routeParameters,
        $cliUser
    ) {
        $this->response = $response;
        $this->serverRepo = $em->getRepository(Server::CLASS);

        $this->parameters = $routeParameters;
        $this->cliUser = $cliUser;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        $serverId = $this->parameters['id'];
        $key = sprintf(static::CACHE_SERVER_STATUS, md5($serverId));

        if ($result = $this->getFromCache($key)) {
            $unjson = json_decode($result, true);
            return $this->setResponse($unjson);
        }

        $result = [
            'id' => 'unknown',
            'status' => static::VALUE_DOWN
        ];

        if ($server = $this->serverRepo->find($serverId)) {

            $result['id'] = $server->getId();
            $result['status'] = static::VALUE_MAYBE;

            if ($server->getType() === 'rsync') {
                $result['status'] = $this->getActualServerStatus($server->getName());
            }
        }

        // Cache even if server is invalid or down
        $this->setToCache($key, json_encode($result));
        $this->setResponse($result);
    }

    /**
     * @param array $payload
     *
     * @return void
     */
    private function setResponse(array $payload)
    {
        $encoded = json_encode($payload);

        $this->response->setBody($encoded);
        $this->response->headers->set('Content-Type', 'application/json');
    }

    /**
     * @param string $servername
     *
     * @return string
     */
    private function getActualServerStatus($servername)
    {
        $syncingUser = 'codexfer';

        $command = sprintf(
            'sudo -u %s ssh -q -o BatchMode=yes %s@%s exit',
            $this->cliUser,
            $syncingUser,
            $servername
        );

        exec($command, $output, $exitCode);

        if ($exitCode === 0) {
            return static::VALUE_UP;

        } else {
            return static::VALUE_DOWN;
        }
    }
}
