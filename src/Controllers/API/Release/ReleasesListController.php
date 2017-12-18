<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\API\Release;

use Doctrine\ORM\EntityManagerInterface;
use Hal\Core\Entity\Release;
use Hal\Core\Repository\ReleaseRepository;
use Hal\UI\API\Hyperlink;
use Hal\UI\API\HypermediaResource;
use Hal\UI\API\Normalizer\ReleaseNormalizer;
use Hal\UI\API\ResponseFormatter;
use Hal\UI\Controllers\APITrait;
use Hal\UI\Controllers\PaginationTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Panthor\ControllerInterface;
use QL\Panthor\HTTPProblem\ProblemRendererInterface;

class ReleasesListController implements ControllerInterface
{
    use APITrait;
    use PaginationTrait;

    private const MAX_PER_PAGE = 50;

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
     * @param ReleaseNormalizer $normalizer
     * @param ProblemRendererInterface $problem
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
        $page = $this->getCurrentPage($request);
        if ($page === null) {
            return $this->withProblem($this->problem, $response, 404, self::ERR_PAGE);
        }

        $pagination = $this->releaseRepository->getPagedResults(self::MAX_PER_PAGE, ($page - 1));
        $total = count($pagination);

        $releases = [];
        foreach ($pagination as $push) {
            $releases[] = $push;
        }

        $links = $this->buildPaginationLinks('api.release.list.paged', $page, $total, self::MAX_PER_PAGE);

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
