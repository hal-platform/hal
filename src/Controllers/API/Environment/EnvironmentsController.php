<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\API\Environment;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Hal\UI\Api\Normalizer\EnvironmentNormalizer;
use Hal\UI\Api\ResponseFormatter;
use Hal\UI\Api\Utility\HypermediaResourceTrait;
use Hal\UI\Controllers\APITrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Hal\Core\Entity\Environment;
use QL\Panthor\ControllerInterface;

class EnvironmentsController implements ControllerInterface
{
    use APITrait;
    use HypermediaResourceTrait;

    /**
     * @var ResponseFormatter
     */
    private $formatter;

    /**
     * @var EntityRepository
     */
    private $envRepo;

    /**
     * @var EnvironmentNormalizer
     */
    private $normalizer;

    /**
     * @param ResponseFormatter $formatter
     * @param EntityManagerInterface $em
     * @param EnvironmentNormalizer $normalizer
     */
    public function __construct(
        ResponseFormatter $formatter,
        EntityManagerInterface $em,
        EnvironmentNormalizer $normalizer
    ) {
        $this->formatter = $formatter;
        $this->envRepo = $em->getRepository(Environment::CLASS);
        $this->normalizer = $normalizer;
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $environments = $this->envRepo->findBy([], ['id' => 'ASC']);
        $status = (count($environments) > 0) ? 200 : 404;

        $environments = array_map(function ($environment) {
            return $this->normalizer->link($environment);
        }, $environments);

        $resource = $this->buildResource(['count' => count($environments)], [], ['environments' => $environments]);
        $data = $this->formatter->buildResponse($request, $resource);

        return $this->withHypermediaEndpoint($request, $response, $data, $status);
    }
}
