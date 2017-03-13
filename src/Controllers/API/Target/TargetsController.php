<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\API\Target;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Hal\UI\API\HypermediaResource;
use Hal\UI\API\ResponseFormatter;
use Hal\UI\Controllers\APITrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Hal\Core\Entity\Application;
use QL\Hal\Core\Entity\Deployment;
use QL\Hal\Core\Entity\Environment;
use QL\Hal\Core\Utility\SortingTrait;
use QL\Panthor\ControllerInterface;

class TargetsController implements ControllerInterface
{
    use APITrait;
    use SortingTrait;

    /**
     * @var ResponseFormatter
     */
    private $formatter;

    /**
     * @var EntityRepository
     */
    private $targetRepo;
    private $environmentRepo;

    /**
     * @param ResponseFormatter $formatter
     * @param EntityManagerInterface $em
     */
    public function __construct(ResponseFormatter $formatter, EntityManagerInterface $em)
    {
        $this->formatter = $formatter;

        $this->targetRepo = $em->getRepository(Deployment::class);
        $this->environmentRepo = $em->getRepository(Environment::class);
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $application = $request->getAttribute(Application::class);

        // Optional filter
        $environment = $this->getEnvironment($request);

        if ($environment) {
            $targets = $this->targetRepo->getDeploymentsByApplicationEnvironment($application, $environment);
        } else {
            $targets = $this->targetRepo->findBy(['application' => $application]);
        }

        usort($targets, $this->deploymentSorter());

        $data = [
            'count' => count($targets)
        ];

        $resource = new HypermediaResource($data, [], [
            'targets' => $targets
        ]);

        $body = $this->formatter->buildHypermediaResponse($request, $resource);
        return $this->withHypermediaEndpoint($request, $response, $body);
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return Environment|null
     */
    private function getEnvironment(ServerRequestInterface $request): ?Environment
    {
        $environmentID = $request->getQueryParams()['environment'] ?? '';

        if (!$environmentID) {
            return null;
        }

        // try by id
        if ($environment = $this->environmentRepo->find($environmentID)) {
            return $environment;
        }

        // try by name
        if ($environment = $this->environmentRepo->findOneBy(['name' => strtolower($environmentID)])) {
            return $environment;
        }

        return null;
    }

}
