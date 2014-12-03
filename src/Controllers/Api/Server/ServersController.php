<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Api\Server;

use QL\Hal\Api\Normalizer\ServerNormalizer;
use QL\Hal\Api\ResponseFormatter;
use QL\Hal\Api\Utility\HypermediaResourceTrait;
use QL\Hal\Core\Entity\Repository\ServerRepository;
use Slim\Http\Request;
use Slim\Http\Response;

/**
 * API Servers Controller
 */
class ServersController
{
    use HypermediaResourceTrait;

    /**
     * @var ResponseFormatter
     */
    private $formatter;

    /**
     * @type ServerRepository
     */
    private $serverRepo;

    /**
     * @var ServerNormalizer
     */
    private $normalizer;

    /**
     * @param ResponseFormatter $formatter
     * @param ServerRepository $serverRepo
     * @param ServerNormalizer $normalizer
     */
    public function __construct(
        ResponseFormatter $formatter,
        ServerRepository $serverRepo,
        ServerNormalizer $normalizer
    ) {
        $this->formatter = $formatter;
        $this->serverRepo = $serverRepo;
        $this->normalizer = $normalizer;
    }

    /**
     * @param Request $request
     * @param Response $response
     */
    public function __invoke(Request $request, Response $response)
    {
        $servers = $this->serverRepo->findBy([], ['id' => 'ASC']);
        $status = (count($servers) > 0) ? 200 : 404;

        $servers = array_map(function ($server) {
            return $this->normalizer->link($server);
        }, $servers);

        $this->formatter->respond($this->buildResource(
            [
                'count' => count($servers)
            ],
            [],
            [
                'servers' => $servers
            ]
        ), $status);
    }
}
