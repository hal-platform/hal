<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\APIInternal;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Hal\UI\API\HypermediaResource;
use Hal\UI\API\Normalizer;
use Hal\UI\API\ResponseFormatter;
use Hal\UI\Controllers\APITrait;
use Hal\UI\Controllers\SessionTrait;
use Hal\UI\Service\PermissionService;
use Hal\UI\Service\PoolService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Hal\Core\Entity\Application;
use QL\Hal\Core\Entity\Deployment;
use QL\Hal\Core\Entity\Environment;
use QL\Hal\Core\Repository\DeploymentRepository;
use QL\Panthor\ControllerInterface;

class ApplicationStatusController implements ControllerInterface
{
    use APITrait;
    use SessionTrait;

    /**
     * @var PoolService
     */
    private $poolService;

    /**
     * @var PermissionService
     */
    private $permissionService;

    /**
     * @var ResponseFormatter
     */
    private $formatter;

    /**
     * @var Normalizer
     */
    private $normalizer;

    /**
     * @var DeploymentRepository
     */
    private $deploymentRepo;

    /**
     * @param EntityManagerInterface $em
     * @param PoolService $poolService
     * @param PermissionService $permissionService
     * @param ResponseFormatter $formatter
     * @param Normalizer $normalizer
     */
    public function __construct(
        EntityManagerInterface $em,
        PoolService $poolService,
        PermissionService $permissionService,
        ResponseFormatter $formatter,
        Normalizer $normalizer
    ) {
        $this->poolService = $poolService;
        $this->permissionService = $permissionService;
        $this->formatter = $formatter;
        $this->normalizer = $normalizer;

        $this->deploymentRepo = $em->getRepository(Deployment::class);
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $application = $request->getAttribute(Application::class);
        $environment = $request->getAttribute(Environment::class);

        $user = $this->getUser($request);

        // Get deployments and latest pushes
        $available = $this->deploymentRepo->getDeploymentsByApplicationEnvironment($application, $environment);

        $statuses = [];
        foreach ($available as $deployment) {
            $push = $deployment->push();
            $build = ($push) ? $push->build() : null;

            $resource = new HypermediaResource([], [], [
                'target' => $deployment,
                'push' => $push,
                'build' => $build
            ]);

            $statuses[] = $resource->withEmbedded(['target', 'push', 'build']);
        }

        $canPush = $this->permissionService->canUserPush($user, $application, $environment);

        $data = [
            'view' => $this->getSelectedView($request, $application, $environment),
            'permission' => $canPush
        ];

        $resource = new HypermediaResource($data, [], [
            'statuses' => $statuses
        ]);

        $resource->withEmbedded(['statuses']);

        $body = $this->formatter->buildHypermediaResponse($request, $resource);
        return $this->withHypermediaEndpoint($request, $response, $body, 200);
    }

    /**
     * @param ServerRequestInterface $request
     * @param Application $application
     * @param Environment $environment
     *
     * @return array|null
     */
    private function getSelectedView(ServerRequestInterface $request, Application $application, Environment $environment)
    {
        // Get selected view user has saved
        $views = $this->poolService->getViews($application, $environment);
        $selectedViewID = $this->poolService->findSelectedView($request, $application, $environment, $views);

        foreach ($views as $id => $view) {
            if ($selectedViewID === $id) {
                return $view;
            }
        }

        return null;
    }
}