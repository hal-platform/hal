<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\API\Server;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Hal\UI\API\Hyperlink;
use Hal\UI\API\Normalizer\ServerNormalizer;
use Hal\UI\API\ResponseFormatter;
use Hal\UI\API\Utility\HypermediaResourceTrait;
use Hal\UI\Controllers\APITrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Hal\Core\Entity\Server;
use QL\Panthor\ControllerInterface;
use QL\Panthor\HTTPProblem\ProblemRendererInterface;

class ServersController implements ControllerInterface
{
    use APITrait;
    use HypermediaResourceTrait;

    private const MAX_PER_PAGE = 25;

    private const ERR_PAGE = 'Invalid page ID specified';

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
     * @var ProblemRendererInterface
     */
    private $problemRenderer;

    /**
     * @param ResponseFormatter $formatter
     * @param EntityManagerInterface $em
     * @param ServerNormalizer $normalizer
     * @param ProblemRendererInterface $problemRenderer
     */
    public function __construct(
        ResponseFormatter $formatter,
        EntityManagerInterface $em,
        ServerNormalizer $normalizer,
        ProblemRendererInterface $problemRenderer
    ) {
        $this->formatter = $formatter;
        $this->serverRepo = $em->getRepository(Server::class);
        $this->normalizer = $normalizer;
        $this->problemRenderer = $problemRenderer;
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $params = $request
            ->getAttribute('route')
            ->getArguments();

        $page = $this->getCurrentPage($params);
        if ($page === false) {
            return $this->withProblem($this->problemRenderer, $response, 404, self::ERR_PAGE);
        }

        $pagination = $this->serverRepo->getPaginatedServers(self::MAX_PER_PAGE, ($page - 1));
        $total = count($pagination);

        $servers = [];
        foreach ($pagination as $server) {
            $servers[] = $this->normalizer->link($server);
        }

        $links = $this->buildPaginationLinks($page, $total);
        $links['servers'] = $servers;

        $data = [
            'count' => count($servers),
            'total' => $total,
            'page' => $page
        ];

        $resource = $this->buildResource($data, [], $links);

        $status = (count($servers) > 0) ? 200 : 404;
        $data = $this->formatter->buildResponse($request, $resource);

        return $this->withHypermediaEndpoint($request, $response, $data, $status);
    }

    /**
     * get current page as an int Returns boolean false if page is invalid
     *
     * Make sure to check return with triple equals
     *
     * @param array $routeParams
     *
     * @return bool|int
     */
    private function getCurrentPage(array $routeParams)
    {
        $page = (isset($routeParams['page'])) ? intval($routeParams['page']) : 1;

        // 404, invalid page
        if ($page < 1) {
            return false;
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
