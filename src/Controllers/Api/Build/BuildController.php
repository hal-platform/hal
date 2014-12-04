<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Api\Build;

use QL\Hal\Api\ResponseFormatter;
use QL\Hal\Core\Entity\Build;
use QL\Hal\Core\Entity\Repository\BuildRepository;
use QL\HttpProblem\HttpProblemException;
use Slim\Http\Request;
use Slim\Http\Response;

/**
 * API Build Controller
 */
class BuildController
{
    /**
     * @var ResponseFormatter
     */
    private $formatter;

    /**
     * @type BuildRepository
     */
    private $buildRepo;

    /**
     * @param ResponseFormatter $formatter
     * @param BuildRepository $buildRepo
     */
    public function __construct(
        ResponseFormatter $formatter,
        BuildRepository $buildRepo
    ) {
        $this->formatter = $formatter;
        $this->buildRepo = $buildRepo;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $params
     * @throws HttpProblemException
     */
    public function __invoke(Request $request, Response $response, array $params = [])
    {
        $build = $this->buildRepo->findOneBy(['id' => $params['id']]);

        if (!$build instanceof Build) {
            throw HttpProblemException::build(404, 'invalid-build');
        }

        $this->formatter->respond($build);
    }
}
