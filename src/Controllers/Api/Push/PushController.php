<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Api\Push;

use QL\Hal\Api\ResponseFormatter;
use QL\Hal\Core\Entity\Push;
use QL\Hal\Core\Entity\Repository\PushRepository;
use QL\HttpProblem\HttpProblemException;
use Slim\Http\Request;
use Slim\Http\Response;

/**
 * API Push Controller
 */
class PushController
{
    /**
     * @var ResponseFormatter
     */
    private $formatter;

    /**
     * @type PushRepository
     */
    private $pushRepo;

    /**
     * @param ResponseFormatter $formatter
     * @param PushRepository $pushRepo
     */
    public function __construct(
        ResponseFormatter $formatter,
        PushRepository $pushRepo
    ) {
        $this->formatter = $formatter;
        $this->pushRepo = $pushRepo;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $params
     * @throws HttpProblemException
     */
    public function __invoke(Request $request, Response $response, array $params = [])
    {
        $push = $this->pushRepo->find($params['id']);

        if (!$push instanceof Push) {
            throw HttpProblemException::build(404, 'invalid-push');
        }

        $this->formatter->respond($push);
    }
}
