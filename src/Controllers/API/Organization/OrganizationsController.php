<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\API\Organization;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Hal\UI\Controllers\APITrait;
use Hal\UI\API\HypermediaResource;
use Hal\UI\API\ResponseFormatter;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Hal\Core\Entity\Group;
use QL\Panthor\ControllerInterface;

class OrganizationsController implements ControllerInterface
{
    use APITrait;

    /**
     * @var ResponseFormatter
     */
    private $formatter;

    /**
     * @var EntityRepository
     */
    private $groupRepo;

    /**
     * @param ResponseFormatter $formatter
     * @param EntityManagerInterface $em
     */
    public function __construct(ResponseFormatter $formatter, EntityManagerInterface $em)
    {
        $this->formatter = $formatter;
        $this->groupRepo = $em->getRepository(Group::class);
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $organizations = $this->groupRepo->findBy([], ['id' => 'ASC']);

        $data = [
            'count' => count($organizations)
        ];

        $resource = new HypermediaResource($data, [], [
            'organizations' => $organizations
        ]);

        $status = (count($organizations) > 0) ? 200 : 404;
        $data = $this->formatter->buildHypermediaResponse($request, $resource);

        return $this->withHypermediaEndpoint($request, $response, $data, $status);
    }
}
