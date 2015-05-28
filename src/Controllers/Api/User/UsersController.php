<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Api\User;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use QL\Hal\Api\Normalizer\UserNormalizer;
use QL\Hal\Api\ResponseFormatter;
use QL\Hal\Api\Utility\HypermediaResourceTrait;
use QL\Hal\Core\Entity\User;
use QL\Hal\Core\Repository\UserRepository;
use QL\Panthor\ControllerInterface;

class UsersController implements ControllerInterface
{
    use HypermediaResourceTrait;

    /**
     * @type ResponseFormatter
     */
    private $formatter;

    /**
     * @type EntityRepository
     */
    private $userRepo;

    /**
     * @type UserNormalizer
     */
    private $normalizer;

    /**
     * @param ResponseFormatter $formatter
     * @param EntityManagerInterface $em
     * @param UserNormalizer $normalizer
     */
    public function __construct(
        ResponseFormatter $formatter,
        EntityManagerInterface $em,
        UserNormalizer $normalizer
    ) {
        $this->formatter = $formatter;
        $this->userRepo = $em->getRepository(User::CLASS);
        $this->normalizer = $normalizer;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
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
