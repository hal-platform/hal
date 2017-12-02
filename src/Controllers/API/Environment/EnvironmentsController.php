<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\API\Environment;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Hal\Core\Entity\Environment;
use Hal\UI\API\HypermediaResource;
use Hal\UI\API\ResponseFormatter;
use Hal\UI\Controllers\APITrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Panthor\ControllerInterface;

class EnvironmentsController implements ControllerInterface
{
    use APITrait;

    /**
     * @var ResponseFormatter
     */
    private $formatter;

    /**
     * @var EntityRepository
     */
    private $envRepo;

    /**
     * @param ResponseFormatter $formatter
     * @param EntityManagerInterface $em
     */
    public function __construct(ResponseFormatter $formatter, EntityManagerInterface $em)
    {
        $this->formatter = $formatter;
        $this->envRepo = $em->getRepository(Environment::class);
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $environments = $this->envRepo->findBy([], ['id' => 'ASC']);

        $data = [
            'count' => count($environments)
        ];

        $resource = new HypermediaResource($data, [], [
            'environments' => $environments
        ]);

        $status = (count($environments) > 0) ? 200 : 404;
        $data = $this->formatter->buildHypermediaResponse($request, $resource);

        return $this->withHypermediaEndpoint($request, $response, $data, $status);
    }
}
