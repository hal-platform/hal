<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\API\Target;

use Doctrine\ORM\EntityManagerInterface;
use Hal\UI\API\Normalizer\PushNormalizer;
use Hal\UI\API\ResponseFormatter;
use Hal\UI\API\Utility\HypermediaResourceTrait;
use Hal\UI\Controllers\APITrait;
use Hal\UI\Controllers\PaginationTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Hal\Core\Entity\Deployment;
use QL\Hal\Core\Entity\Push;
use QL\Hal\Core\Repository\PushRepository;
use QL\Hal\Core\Type\EnumType\PushStatusEnum;
use QL\Panthor\ControllerInterface;
use QL\Panthor\HTTPProblem\ProblemRendererInterface;

class HistoryController implements ControllerInterface
{
    use APITrait;
    use HypermediaResourceTrait;
    use PaginationTrait;

    const MAX_PER_PAGE = 25;

    private const ERR_PAGE = 'Invalid page specified';

    /**
     * @var ResponseFormatter
     */
    private $formatter;

    /**
     * @var PushRepository
     */
    private $pushRepo;

    /**
     * @var PushNormalizer
     */
    private $normalizer;

    /**
     * @var ProblemRendererInterface
     */
    private $problem;

    /**
     * @param ResponseFormatter $formatter
     * @param EntityManagerInterface $em
     * @param PushNormalizer $normalizer
     * @param array $parameters
     */
    public function __construct(
        ResponseFormatter $formatter,
        EntityManagerInterface $em,
        PushNormalizer $normalizer,
        ProblemRendererInterface $problem
    ) {
        $this->formatter = $formatter;
        $this->pushRepo = $em->getRepository(Push::class);
        $this->normalizer = $normalizer;
        $this->problem = $problem;
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $target = $request->getAttribute(Deployment::class);

        $page = $this->getCurrentPage($request);
        if ($page === null) {
            return $this->withProblem($this->problem, $response, 404, self::ERR_PAGE);
        }

        $pagination = $this->pushRepo->getByDeployment($target, self::MAX_PER_PAGE, ($page - 1));
        $total = count($pagination);

        $pushes = [];
        foreach ($pagination as $push) {
            $pushes[] = $this->normalizer->link($push);
        }

        $links = $this->buildPaginationLinks('api.target.history.paged', $page, $total, self::MAX_PER_PAGE, ['target' => $target->id()]);
        $links['pushes'] = $pushes;

        $data = [
            'count' => count($pushes),
            'total' => $total,
            'page' => $page
        ];

        $resource = $this->buildResource($data, [], $links);

        $status = (count($pushes) > 0) ? 200 : 404;
        $data = $this->formatter->buildResponse($request, $resource);

        return $this->withHypermediaEndpoint($request, $response, $data, $status);
    }
}