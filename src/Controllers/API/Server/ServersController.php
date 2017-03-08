<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\API\Server;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Hal\UI\API\Normalizer\ServerNormalizer;
use Hal\UI\API\ResponseFormatter;
use Hal\UI\API\Utility\HypermediaResourceTrait;
use Hal\UI\Controllers\APITrait;
use Hal\UI\Controllers\PaginationTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Hal\Core\Entity\Server;
use QL\Panthor\ControllerInterface;
use QL\Panthor\HTTPProblem\ProblemRendererInterface;

class ServersController implements ControllerInterface
{
    use APITrait;
    use HypermediaResourceTrait;
    use PaginationTrait;

    private const MAX_PER_PAGE = 25;

    private const ERR_PAGE = 'Invalid page specified';

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
    private $problem;

    /**
     * @param ResponseFormatter $formatter
     * @param EntityManagerInterface $em
     * @param ServerNormalizer $normalizer
     * @param ProblemRendererInterface $problem
     */
    public function __construct(
        ResponseFormatter $formatter,
        EntityManagerInterface $em,
        ServerNormalizer $normalizer,
        ProblemRendererInterface $problem
    ) {
        $this->formatter = $formatter;
        $this->serverRepo = $em->getRepository(Server::class);
        $this->normalizer = $normalizer;
        $this->problem = $problem;
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $page = $this->getCurrentPage($request);
        if ($page === null) {
            return $this->withProblem($this->problem, $response, 404, self::ERR_PAGE);
        }

        $pagination = $this->serverRepo->getPaginatedServers(self::MAX_PER_PAGE, ($page - 1));
        $total = count($pagination);

        $servers = [];
        foreach ($pagination as $server) {
            $servers[] = $this->normalizer->link($server);
        }

        $links = $this->buildPaginationLinks('api.servers.paged', $page, $total, self::MAX_PER_PAGE);
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
}
