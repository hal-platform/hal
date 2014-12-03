<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Api\Server;

use QL\Hal\Api\ResponseFormatter;
use QL\Hal\Core\Entity\Repository\ServerRepository;
use QL\Hal\Core\Entity\Server;
use QL\HttpProblem\HttpProblemException;
use Slim\Http\Request;
use Slim\Http\Response;

/**
 * API Server Controller
 */
class ServerController
{
    /**
     * @var ResponseFormatter
     */
    private $formatter;

    /**
     * @type ServerRepository
     */
    private $serverRepo;

    /**
     * @param ResponseFormatter $formatter
     * @param ServerRepository $serverRepo
     */
    public function __construct(
        ResponseFormatter $formatter,
        ServerRepository $serverRepo
    ) {
        $this->formatter = $formatter;
        $this->serverRepo = $serverRepo;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $params
     * @throws HttpProblemException
     */
    public function __invoke(Request $request, Response $response, array $params = [])
    {
        $server = $this->serverRepo->findOneBy(['id' => $params['id']]);

        if (!$server instanceof Server) {
            throw HttpProblemException::build(404, 'invalid-server');
        }

        $this->formatter->respond($server);
    }
}
