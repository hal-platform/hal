<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\API\Group;

use Doctrine\ORM\EntityManagerInterface;
use Hal\Core\Entity\Group;
use Hal\Core\Repository\GroupRepository;
use Hal\UI\API\HypermediaResource;
use Hal\UI\API\ResponseFormatter;
use Hal\UI\Controllers\APITrait;
use Hal\UI\Controllers\PaginationTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Panthor\ControllerInterface;
use QL\Panthor\HTTPProblem\ProblemRendererInterface;

class GroupsController implements ControllerInterface
{
    use APITrait;
    use PaginationTrait;

    private const MAX_PER_PAGE = 25;

    private const ERR_PAGE = 'Invalid page specified';

    /**
     * @var ResponseFormatter
     */
    private $formatter;

    /**
     * @var GroupRepository
     */
    private $groupRepository;

    /**
     * @var ProblemRendererInterface
     */
    private $problem;

    /**
     * @param ResponseFormatter $formatter
     * @param EntityManagerInterface $em
     * @param ProblemRendererInterface $problem
     */
    public function __construct(
        ResponseFormatter $formatter,
        EntityManagerInterface $em,
        ProblemRendererInterface $problem
    ) {
        $this->formatter = $formatter;
        $this->groupRepository = $em->getRepository(Group::class);
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

        $pagination = $this->groupRepository->getPagedResults(self::MAX_PER_PAGE, ($page - 1));
        $total = count($pagination);

        $servers = [];
        foreach ($pagination as $server) {
            $servers[] = $server;
        }

        $links = $this->buildPaginationLinks('api.groups.paged', $page, $total, self::MAX_PER_PAGE);

        $data = [
            'count' => count($servers),
            'total' => $total,
            'page' => $page
        ];

        $resource = new HypermediaResource($data, $links, [
            'groups' => $servers
        ]);

        $status = (count($servers) > 0) ? 200 : 404;
        $data = $this->formatter->buildHypermediaResponse($request, $resource);

        return $this->withHypermediaEndpoint($request, $response, $data, $status);
    }
}
