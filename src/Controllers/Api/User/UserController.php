<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Api\User;

use QL\Hal\Api\ResponseFormatter;
use QL\Hal\Core\Repository\UserRepository;
use QL\Hal\Core\Entity\User;
use QL\HttpProblem\HttpProblemException;
use QL\Panthor\ControllerInterface;

class UserController implements ControllerInterface
{
    /**
     * @var ResponseFormatter
     */
    private $formatter;

    /**
     * @type UserRepository
     */
    private $userRepo;

    /**
     * @type array
     */
    private $parameters;

    /**
     * @param ResponseFormatter $formatter
     * @param UserRepository $userRepo
     * @param array $parameters
     */
    public function __construct(ResponseFormatter $formatter, UserRepository $userRepo, array $parameters)
    {
        $this->formatter = $formatter;
        $this->userRepo = $userRepo;
        $this->parameters = $parameters;
    }

    /**
     * {@inheritdoc}
     * @throws HttpProblemException
     */
    public function __invoke()
    {
        $user = $this->userRepo->find($this->parameters['id']);

        if (!$user instanceof User) {
            throw HttpProblemException::build(404, 'invalid-user');
        }

        $this->formatter->respond($user);
    }
}
