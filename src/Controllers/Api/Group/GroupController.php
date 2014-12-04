<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Api\Group;

use QL\Hal\Api\ResponseFormatter;
use QL\Hal\Core\Entity\Group;
use QL\Hal\Core\Entity\Repository\GroupRepository;
use QL\HttpProblem\HttpProblemException;
use Slim\Http\Request;
use Slim\Http\Response;

/**
 * API Group Controller
 */
class GroupController
{
    /**
     * @var ResponseFormatter
     */
    private $formatter;

    /**
     * @type GroupRepository
     */
    private $groupRepo;

    /**
     * @param ResponseFormatter $formatter
     * @param GroupRepository $groupRepo
     */
    public function __construct(
        ResponseFormatter $formatter,
        GroupRepository $groupRepo
    ) {
        $this->formatter = $formatter;
        $this->groupRepo = $groupRepo;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $params
     * @throws HttpProblemException
     */
    public function __invoke(Request $request, Response $response, array $params = [])
    {
        $group = $this->groupRepo->findOneBy(['id' => $params['id']]);

        if (!$group instanceof Group) {
            throw HttpProblemException::build(404, 'invalid-group');
        }

        $this->formatter->respond($group);
    }
}
