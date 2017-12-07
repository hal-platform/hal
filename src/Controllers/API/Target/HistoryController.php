<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\API\Target;

use Doctrine\ORM\EntityManagerInterface;
use Hal\Core\Entity\Release;
use Hal\Core\Entity\Target;
use Hal\Core\Repository\ReleaseRepository;
use Hal\UI\API\Hyperlink;
use Hal\UI\API\HypermediaResource;
use Hal\UI\API\ResponseFormatter;
use Hal\UI\Controllers\APITrait;
use Hal\UI\Controllers\PaginationTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Panthor\ControllerInterface;
use QL\Panthor\HTTPProblem\ProblemRendererInterface;

class HistoryController implements ControllerInterface
{
    use APITrait;
    use PaginationTrait;

    const MAX_PER_PAGE = 25;

    private const ERR_PAGE = 'Invalid page specified';

    /**
     * @var ResponseFormatter
     */
    private $formatter;

    /**
     * @var ReleaseRepository
     */
    private $releaseRepository;

    /**
     * @var ProblemRendererInterface
     */
    private $problem;

    /**
     * @param ResponseFormatter $formatter
     * @param EntityManagerInterface $em
     * @param array $parameters
     */
    public function __construct(
        ResponseFormatter $formatter,
        EntityManagerInterface $em,
        ProblemRendererInterface $problem
    ) {
        $this->formatter = $formatter;
        $this->releaseRepository = $em->getRepository(Release::class);
        $this->problem = $problem;
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $target = $request->getAttribute(Target::class);

        $page = $this->getCurrentPage($request);
        if ($page === null) {
            return $this->withProblem($this->problem, $response, 404, self::ERR_PAGE);
        }

        $pagination = $this->releaseRepository->getByTarget($target, self::MAX_PER_PAGE, ($page - 1));
        $total = count($pagination);

        $releases = [];
        foreach ($pagination as $push) {
            $releases[] = $push;
        }

        $links = $this->buildPaginationLinks('api.target.history.paged', $page, $total, self::MAX_PER_PAGE, ['target' => $target->id()]);
        $links['target'] = new Hyperlink(['api.target', ['target' => $target->id()]]);

        $data = [
            'count' => count($releases),
            'total' => $total,
            'page' => $page
        ];

        $resource = new HypermediaResource($data, $links, [
            'releases' => $releases
        ]);

        $status = (count($releases) > 0) ? 200 : 404;
        $data = $this->formatter->buildHypermediaResponse($request, $resource);

        return $this->withHypermediaEndpoint($request, $response, $data, $status);
    }
}
