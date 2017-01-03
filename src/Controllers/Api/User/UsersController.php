<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Hal\Controllers\Api\User;

use Doctrine\ORM\EntityManagerInterface;
use QL\Hal\Api\Hyperlink;
use QL\Hal\Api\Normalizer\UserNormalizer;
use QL\Hal\Api\ResponseFormatter;
use QL\Hal\Api\Utility\HypermediaResourceTrait;
use QL\Hal\Core\Entity\User;
use QL\Hal\Core\Repository\UserRepository;
use QL\Panthor\ControllerInterface;
use QL\Panthor\Exception\HTTPProblemException;

class UsersController implements ControllerInterface
{
    use HypermediaResourceTrait;

    const MAX_PER_PAGE = 25;

    /**
     * @var ResponseFormatter
     */
    private $formatter;

    /**
     * @var UserRepository
     */
    private $userRepo;

    /**
     * @var UserNormalizer
     */
    private $normalizer;

    /**
     * @var array
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
     * @throws HTTPProblemException
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
     * @throws HTTPProblemException
     *
     * @return int
     */
    private function getCurrentPage()
    {
        $page = (isset($this->parameters['page'])) ? intval($this->parameters['page']) : 1;

        // 404, invalid page
        if ($page < 1) {
            throw new HTTPProblemException(404, 'Invalid page ID specified');
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
            $links['prev'] = new Hyperlink(['api.users.paged', ['page' => $prev]]);
        }

        if ($next <= $last) {
            $links['next'] = new Hyperlink(['api.users.paged', ['page' => $next]]);
        }

        if ($last > 1 && $current > 1) {
            $links['first'] = new Hyperlink(['api.users.paged', ['page' => '1']]);
        }

        if ($last > 1) {
            $links['last'] = new Hyperlink(['api.users.paged', ['page' => $last]]);
        }

        return $links;
    }
}
