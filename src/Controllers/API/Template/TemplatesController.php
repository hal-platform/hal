<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\API\Template;

use Doctrine\ORM\EntityManagerInterface;
use Hal\Core\Entity\TargetTemplate;
use Hal\Core\Repository\TargetTemplateRepository;
use Hal\UI\API\HypermediaResource;
use Hal\UI\API\ResponseFormatter;
use Hal\UI\Controllers\APITrait;
use Hal\UI\Controllers\PaginationTrait;
use Hal\UI\SharedStaticConfiguration;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Panthor\ControllerInterface;
use QL\Panthor\HTTPProblem\ProblemRendererInterface;

class TemplatesController implements ControllerInterface
{
    use APITrait;
    use PaginationTrait;

    private const ERR_PAGE = 'Invalid page specified';

    /**
     * @var ResponseFormatter
     */
    private $formatter;

    /**
     * @var TargetTemplateRepository
     */
    private $templateRepository;

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
        $this->templateRepository = $em->getRepository(TargetTemplate::class);
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

        $pagination = $this->templateRepository->getPagedResults(SharedStaticConfiguration::LARGE_PAGE_SIZE, ($page - 1));
        $total = count($pagination);

        $templates = [];
        foreach ($pagination as $template) {
            $templates[] = $template;
        }

        $links = $this->buildPaginationLinks('api.templates.paged', $page, $total, SharedStaticConfiguration::LARGE_PAGE_SIZE);

        $data = [
            'count' => count($templates),
            'total' => $total,
            'page' => $page,
        ];

        $resource = new HypermediaResource($data, $links, [
            'templates' => $templates,
        ]);

        $status = (count($templates) > 0) ? 200 : 404;
        $data = $this->formatter->buildHypermediaResponse($request, $resource);

        return $this->withHypermediaEndpoint($request, $response, $data, $status);
    }
}
