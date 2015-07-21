<?php
/**
 * @copyright ©2015 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Api\User;

use Doctrine\ORM\EntityManagerInterface;
use QL\Hal\Api\Normalizer\UserNormalizer;
use QL\Hal\Api\ResponseFormatter;
use QL\Hal\Api\Utility\HypermediaResourceTrait;
use QL\Hal\Core\Entity\User;
use QL\Hal\Core\Repository\UserRepository;
use QL\HttpProblem\HttpProblemException;
use QL\Panthor\ControllerInterface;

class UsersController implements ControllerInterface
{
    use HypermediaResourceTrait;

    const MAX_PER_PAGE = 25;

    /**
     * @type ResponseFormatter
     */
    private $formatter;

    /**
     * @type UserRepository
     */
    private $userRepo;

    /**
     * @type UserNormalizer
     */
    private $normalizer;

    /**
     * @type array
     */
    private $parameters;

    /**
     * @param ResponseFormatter $formatter
     * @param EntityManagerInterface $em
     * @param UserNormalizer $normalizer
     * @param array $parameters
     */
    public function __construct(
        ResponseFormatter $formatter,
        EntityManagerInterface $em,
        UserNormalizer $normalizer,
        array $parameters
    ) {
        $this->formatter = $formatter;
        $this->userRepo = $em->getRepository(User::CLASS);
        $this->normalizer = $normalizer;

        $this->parameters = $parameters;
    }

    /**
     * {@inheritdoc}
     * @throws HttpProblemException
     */
    public function __invoke()
    {
        $page = $this->getCurrentPage();

        $pagination = $this->userRepo->getPaginatedUsers(self::MAX_PER_PAGE, ($page - 1));
        $total = count($pagination);

        $users = [];
        foreach ($pagination as $user) {
            $users[] = $this->normalizer->link($user);
        }

        $links = $this->buildPaginationLinks($page, $total);
        $links['users'] = $users;

        $resource = $this->buildResource(
            [
                'count' => count($users),
                'total' => $total,
                'page' => $page
            ],
            [],
            $links
        );

        $status = (count($users) > 0) ? 200 : 404;
        $this->formatter->respond($resource, $status);
    }

    /**
     * @throws HttpProblemException
     *
     * @return int
     */
    private function getCurrentPage()
    {
        $page = (isset($this->parameters['page'])) ? intval($this->parameters['page']) : 1;

        // 404, invalid page
        if ($page < 1) {
            throw HttpProblemException::build(404, 'invalid-page');
        }

        return $page;
    }

    /**
     * @param int $current
     * @param int $last
     *
     * @return array
     */
    private function buildPaginationLinks($current, $total)
    {
        $links = [];

        $prev = $current - 1;
        $next = $current + 1;
        $last = ceil($total / self::MAX_PER_PAGE);

        if ($current > 1) {
            $links['prev'] = ['href' => ['api.users.paged', ['page' => $prev]]];
        }

        if ($next <= $last) {
            $links['next'] = ['href' => ['api.users.paged', ['page' => $next]]];
        }

        if ($last > 1 && $current > 1) {
            $links['first'] = ['href' => ['api.users.paged', ['page' => '1']]];
        }

        if ($last > 1) {
            $links['last'] = ['href' => ['api.users.paged', ['page' => $last]]];
        }

        return $links;
    }
}
