<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\API\Application;

use Doctrine\ORM\EntityManagerInterface;
use Hal\Core\Entity\Application;
use Hal\Core\Repository\ApplicationRepository;
use Hal\UI\API\HypermediaResource;
use Hal\UI\API\ResponseFormatter;
use Hal\UI\Controllers\APITrait;
use Hal\UI\Controllers\PaginationTrait;
use Hal\UI\SharedStaticConfiguration;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Panthor\ControllerInterface;
use QL\Panthor\HTTPProblem\ProblemRendererInterface;

class ApplicationsController implements ControllerInterface
{
    use APITrait;
    use PaginationTrait;

    private const ERR_PAGE = 'Invalid page specified';

    /**
     * @var ResponseFormatter
     */
    private $formatter;

    /**
     * @var ApplicationRepository
     */
    private $applicationRepo;

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
        $this->applicationRepo = $em->getRepository(Application::class);
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

        $pagination = $this->applicationRepo->getPagedResults(SharedStaticConfiguration::LARGE_PAGE_SIZE, ($page - 1));

        $total = count($pagination);
        $last = ceil($total / SharedStaticConfiguration::LARGE_PAGE_SIZE);

        $applications = [];
        foreach ($pagination as $application) {
            $applications[] = $application;
        }

        $links = $this->buildPaginationLinks('api.applications.paged', $page, $total, SharedStaticConfiguration::LARGE_PAGE_SIZE);

        $data = [
            'count' => count($applications),
            'total' => $total,
            'page' => $page,
        ];

        $resource = new HypermediaResource($data, $links, [
            'applications' => $applications,
        ]);

        $status = (count($applications) > 0) ? 200 : 404;
        $data = $this->formatter->buildHypermediaResponse($request, $resource);

        return $this->withHypermediaEndpoint($request, $response, $data, $status);
    }
}
