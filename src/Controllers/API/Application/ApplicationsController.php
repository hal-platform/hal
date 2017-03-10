<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\API\Application;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Hal\UI\API\Normalizer\ApplicationNormalizer;
use Hal\UI\API\ResponseFormatter;
use Hal\UI\API\Utility\HypermediaResourceTrait;
use Hal\UI\Controllers\APITrait;
use Hal\UI\Controllers\PaginationTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Hal\Core\Entity\Application;
use QL\Panthor\ControllerInterface;
use QL\Panthor\HTTPProblem\ProblemRendererInterface;

class ApplicationsController implements ControllerInterface
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
    private $applicationRepo;

    /**
     * @var ApplicationNormalizer
     */
    private $normalizer;

    /**
     * @var ProblemRendererInterface
     */
    private $problem;

    /**
     * @param ResponseFormatter $formatter
     * @param EntityManagerInterface $em
     * @param ApplicationNormalizer $normalizer
     * @param ProblemRendererInterface $problem
     */
    public function __construct(
        ResponseFormatter $formatter,
        EntityManagerInterface $em,
        ApplicationNormalizer $normalizer,
        ProblemRendererInterface $problem
    ) {
        $this->formatter = $formatter;
        $this->applicationRepo = $em->getRepository(Application::class);
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

        // @todo add pagination to apps
        $pagination = $this->applicationRepo->findAll();
        // $pagination = $this->applicationRepo->getPaginatedApplications(self::MAX_PER_PAGE, ($page - 1));
        $total = count($pagination);

        $applications = [];
        foreach ($pagination as $application) {
            $applications[] = $this->normalizer->link($application);
        }

        $links = $this->buildPaginationLinks('api.applications.paged', $page, $total, self::MAX_PER_PAGE);
        $links['applications'] = $applications;

        $data = [
            'count' => count($applications),
            'total' => $total,
            'page' => $page
        ];

        $resource = $this->buildResource($data, [], $links);

        $status = (count($applications) > 0) ? 200 : 404;
        $data = $this->formatter->buildResponse($request, $resource);

        return $this->withHypermediaEndpoint($request, $response, $data, $status);
    }
}