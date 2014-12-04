<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Api\User;

use QL\Hal\Api\Normalizer\UserNormalizer;
use QL\Hal\Api\ResponseFormatter;
use QL\Hal\Api\Utility\HypermediaResourceTrait;
use QL\Hal\Core\Entity\Repository\UserRepository;
use Slim\Http\Request;
use Slim\Http\Response;

/**
 * API Users Controller
 */
class UsersController
{
    use HypermediaResourceTrait;

    /**
     * @var ResponseFormatter
     */
    private $formatter;

    /**
     * @type UserRepository
     */
    private $userRepo;

    /**
     * @var UserNormalizer
     */
    private $normalizer;

    /**
     * @param ResponseFormatter $formatter
     * @param UserRepository $userRepo
     * @param UserNormalizer $normalizer
     */
    public function __construct(
        ResponseFormatter $formatter,
        UserRepository $userRepo,
        UserNormalizer $normalizer
    ) {
        $this->formatter = $formatter;
        $this->userRepo = $userRepo;
        $this->normalizer = $normalizer;
    }

    /**
     * @param Request $request
     * @param Response $response
     */
    public function __invoke(Request $request, Response $response)
    {
        $users = $this->userRepo->findBy([], ['id' => 'ASC']);
        $status = (count($users) > 0) ? 200 : 404;

        $users = array_map(function ($user) {
            return $this->normalizer->link($user);
        }, $users);

        $this->formatter->respond($this->buildResource(
            [
                'count' => count($users)
            ],
            [],
            [
                'users' => $users
            ]
        ), $status);
    }
}
