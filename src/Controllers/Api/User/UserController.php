<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Api\User;

use QL\Hal\Api\ResponseFormatter;
use QL\Hal\Core\Entity\Repository\UserRepository;
use QL\Hal\Core\Entity\User;
use QL\HttpProblem\HttpProblemException;
use Slim\Http\Request;
use Slim\Http\Response;

/**
 * API User Controller
 */
class UserController
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
     * @param ResponseFormatter $formatter
     * @param UserRepository $userRepo
     */
    public function __construct(
        ResponseFormatter $formatter,
        UserRepository $userRepo
    ) {
        $this->formatter = $formatter;
        $this->userRepo = $userRepo;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $params
     * @throws HttpProblemException
     */
    public function __invoke(Request $request, Response $response, array $params = [])
    {
        $user = $this->userRepo->findOneBy(['id' => $params['id']]);

        if (!$user instanceof User) {
            throw HttpProblemException::build(404, 'invalid-user');
        }

        $this->formatter->respond($user);
    }
}
