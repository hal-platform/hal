<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Hal\Controllers\Api\Group;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use QL\Hal\Api\ResponseFormatter;
use QL\Hal\Core\Entity\Group;
use QL\Panthor\ControllerInterface;
use QL\Panthor\Exception\HTTPProblemException;

class GroupController implements ControllerInterface
{
    /**
     * @var ResponseFormatter
     */
    private $formatter;

    /**
     * @var EntityRepository
     */
    private $groupRepo;

    /**
     * @var array
     */
    private $parameters;

    /**
     * @param ResponseFormatter $formatter
     * @param EntityManagerInterface $em
     * @param array $parameters
     */
    public function __construct(ResponseFormatter $formatter, EntityManagerInterface $em, array $parameters)
    {
        $this->formatter = $formatter;
        $this->groupRepo = $em->getRepository(Group::CLASS);
        $this->parameters = $parameters;
    }

    /**
     * {@inheritdoc}
     * @throws HTTPProblemException
     */
    public function __invoke()
    {
        $group = $this->groupRepo->find($this->parameters['id']);

        if (!$group instanceof Group) {
            throw new HTTPProblemException(404, 'Invalid Group ID specified');
        }

        $this->formatter->respond($group);
    }
}
