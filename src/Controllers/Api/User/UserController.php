<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Hal\Controllers\Api\User;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use QL\Hal\Api\ResponseFormatter;
use QL\Hal\Core\Repository\UserRepository;
use QL\Hal\Core\Entity\User;
use QL\Panthor\ControllerInterface;
use QL\Panthor\Exception\HTTPProblemException;

class UserController implements ControllerInterface
{
    /**
     * @var ResponseFormatter
     */
    private $formatter;

    /**
     * @var EntityRepository
     */
    private $userRepo;

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
        $this->userRepo = $em->getRepository(User::CLASS);
        $this->parameters = $parameters;
    }

    /**
     * {@inheritdoc}
     * @throws HTTPProblemException
     */
    public function __invoke()
    {
        $user = $this->userRepo->find($this->parameters['id']);

        if (!$user instanceof User) {
            throw new HTTPProblemException(404, 'Invalid user ID specified');
        }

        $this->formatter->respond($user);
    }
}
