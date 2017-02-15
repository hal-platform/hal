<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Api\Server;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Hal\UI\Api\Hyperlink;
use Hal\UI\Api\Normalizer\ServerNormalizer;
use Hal\UI\Api\ResponseFormatter;
use Hal\UI\Api\Utility\HypermediaResourceTrait;
use QL\Hal\Core\Entity\Server;
use QL\Panthor\ControllerInterface;
use QL\Panthor\Exception\HTTPProblemException;

class ServersController implements ControllerInterface
{
    use HypermediaResourceTrait;

    const MAX_PER_PAGE = 25;

    /**
     * @var ResponseFormatter
     */
    private $formatter;

    /**
     * @var EntityRepository
     */
    private $serverRepo;

    /**
     * @var ServerNormalizer
     */
    private $normalizer;

    /**
     * @var array
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
     * @inheritDoc
     * @throws HTTPProblemException
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
            $links['prev'] = new Hyperlink(['api.servers.paged', ['page' => $prev]]);
        }

        if ($next <= $last) {
            $links['next'] = new Hyperlink(['api.servers.paged', ['page' => $next]]);
        }

        if ($last > 1 && $current > 1) {
            $links['first'] = new Hyperlink(['api.servers.paged', ['page' => '1']]);
        }

        if ($last > 1) {
            $links['last'] = new Hyperlink(['api.servers.paged', ['page' => $last]]);
        }

        return $links;
    }
}
