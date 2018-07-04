<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\API\Build;

use Doctrine\ORM\EntityManagerInterface;
use Hal\Core\Entity\Application;
use Hal\Core\Entity\JobType\Build;
use Hal\Core\Repository\JobType\BuildRepository;
use Hal\UI\API\Hyperlink;
use Hal\UI\API\HypermediaResource;
use Hal\UI\API\ResponseFormatter;
use Hal\UI\Controllers\APITrait;
use Hal\UI\Controllers\PaginationTrait;
use Hal\UI\SharedStaticConfiguration;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Panthor\ControllerInterface;
use QL\Panthor\HTTPProblem\ProblemRendererInterface;

class BuildsController implements ControllerInterface
{
    use APITrait;
    use PaginationTrait;

    private const ERR_PAGE = 'Invalid page specified';

    /**
     * @var ResponseFormatter
     */
    private $formatter;

    /**
     * @var BuildRepository
     */
    private $buildRepo;

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
        $this->buildRepo = $em->getRepository(Build::class);
        $this->problem = $problem;
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $application = $request->getAttribute(Application::class);

        $page = $this->getCurrentPage($request);
        $pageSize = SharedStaticConfiguration::LARGE_PAGE_SIZE;

        if ($page === null) {
            return $this->withProblem($this->problem, $response, 404, self::ERR_PAGE);
        }

        $pagination = $this->buildRepo->getByApplication($application, $pageSize, ($page - 1));
        $total = count($pagination);

        $builds = [];
        foreach ($pagination as $build) {
            $builds[] = $build;
        }

        $links = $this->buildPaginationLinks('api.builds.history', $page, $total, $pageSize, ['application' => $application->id()]);

        $data = [
            'count' => count($builds),
            'total' => $total,
            'page' => $page,
        ];

        $resource = new HypermediaResource($data, $links, [
            'builds' => $builds,
        ]);

        $status = (count($builds) > 0) ? 200 : 404;
        $data = $this->formatter->buildHypermediaResponse($request, $resource);

        return $this->withHypermediaEndpoint($request, $response, $data, $status);
    }
}
