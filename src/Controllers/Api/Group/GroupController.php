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
use QL\Panthor\ControllerInterface;

class GroupController implements ControllerInterface
{
    /**
     * @type ResponseFormatter
     */
    private $formatter;

    /**
     * @type GroupRepository
     */
    private $groupRepo;

    /**
     * @type array
     */
    private $parameters;

    /**
     * @param ResponseFormatter $formatter
     * @param GroupRepository $groupRepo
     * @param array $parameters
     */
    public function __construct(ResponseFormatter $formatter, GroupRepository $groupRepo, array $parameters)
    {
        $this->formatter = $formatter;
        $this->groupRepo = $groupRepo;
        $this->parameters = $parameters;
    }

    /**
     * {@inheritdoc}
     * @throws HttpProblemException
     */
    public function __invoke()
    {
        $group = $this->groupRepo->find($this->parameters['id']);

        if (!$group instanceof Group) {
            throw HttpProblemException::build(404, 'invalid-group');
        }

        $this->formatter->respond($group);
    }
}
