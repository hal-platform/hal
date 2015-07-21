<?php
/**
 * @copyright ©2015 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Api\Server;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use QL\Hal\Core\Entity\Server;
use QL\Hal\Api\Normalizer\ServerNormalizer;
use QL\Hal\Api\ResponseFormatter;
use QL\Hal\Api\Utility\HypermediaResourceTrait;
use QL\HttpProblem\HttpProblemException;
use QL\Panthor\ControllerInterface;

class ServersController implements ControllerInterface
{
    use HypermediaResourceTrait;

    const MAX_PER_PAGE = 25;

    /**
     * @type ResponseFormatter
     */
    private $formatter;

    /**
     * @type EntityRepository
     */
    private $serverRepo;

    /**
     * @type ServerNormalizer
     */
    private $normalizer;

    /**
     * @type array
     */
    private $parameters;

    /**
     * @param ResponseFormatter $formatter
     * @param EntityManagerInterface $em
     * @param ServerNormalizer $normalizer
     * @param array $parameters
     */
    public function __construct(
        ResponseFormatter $formatter,
        EntityManagerInterface $em,
        ServerNormalizer $normalizer,
        array $parameters
    ) {
        $this->formatter = $formatter;
        $this->serverRepo = $em->getRepository(Server::CLASS);
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

        $pagination = $this->serverRepo->getPaginatedServers(self::MAX_PER_PAGE, ($page - 1));
        $total = count($pagination);

        $servers = [];
        foreach ($pagination as $server) {
            $servers[] = $this->normalizer->link($server);
        }

        $links = $this->buildPaginationLinks($page, $total);
        $links['servers'] = $servers;

        $resource = $this->buildResource(
            [
                'count' => count($servers),
                'total' => $total,
                'page' => $page
            ],
            [],
            $links
        );

        $status = (count($servers) > 0) ? 200 : 404;
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
            $links['prev'] = ['href' => ['api.servers.paged', ['page' => $prev]]];
        }

        if ($next <= $last) {
            $links['next'] = ['href' => ['api.servers.paged', ['page' => $next]]];
        }

        if ($last > 1 && $current > 1) {
            $links['first'] = ['href' => ['api.servers.paged', ['page' => '1']]];
        }

        if ($last > 1) {
            $links['last'] = ['href' => ['api.servers.paged', ['page' => $last]]];
        }

        return $links;
    }
}
