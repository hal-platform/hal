<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Push;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Hal\UI\Controllers\RedirectableControllerTrait;
use Hal\UI\Controllers\SessionTrait;
use Hal\UI\Controllers\TemplatedControllerTrait;
use Hal\UI\Flash;
use Hal\UI\Service\PoolService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Hal\Core\Entity\Build;
use QL\Hal\Core\Entity\Deployment;
use QL\Hal\Core\Entity\Push;
use QL\Hal\Core\Repository\PushRepository;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use QL\Panthor\Utility\URI;

class StartPushController implements ControllerInterface
{
    use RedirectableControllerTrait;
    use SessionTrait;
    use TemplatedControllerTrait;

    private const ERR_NOT_BUILDABLE = 'This build cannot be deployed.';

    /**
     * @var TemplateInterface
     */
    private $template;

    /**
     * @var EntityRepository
     */
    private $buildRepo;
    private $deploymentRepo;

    /**
     * @var PushRepository
     */
    private $pushRepo;

    /**
     * @var PoolService
     */
    private $poolService;

    /**
     * @var URI
     */
    private $uri;

    /**
     * @param TemplateInterface $template
     * @param EntityManagerInterface $em
     * @param PoolService $poolService
     */
    public function __construct(
        TemplateInterface $template,
        EntityManagerInterface $em,
        PoolService $poolService,
        URI $uri
    ) {
        $this->template = $template;

        $this->buildRepo = $em->getRepository(Build::class);
        $this->pushRepo = $em->getRepository(Push::class);
        $this->deploymentRepo = $em->getRepository(Deployment::class);

        $this->poolService = $poolService;
        $this->uri = $uri;
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $build = $request->getAttribute(Build::class);

        if (!$build->isSuccess()) {
            $this
                ->getFlash($request)
                ->withMessage(Flash::ERROR, self::ERR_NOT_BUILDABLE);

            return $this->withRedirectRoute($response, $this->uri, 'build', ['build' => $build->id()]);
        }

        // Get selected view user has saved
        $views = $this->poolService->getViews($build->application(), $build->environment());
        $selectedView = $this->poolService->findSelectedView($request, $build->application(), $build->environment(), $views);

        $deployments = $this->deploymentRepo->getDeploymentsByApplicationEnvironment($build->application(), $build->environment());
        $statuses = [];
        foreach ($deployments as $deployment) {
            $push = $this->pushRepo->getMostRecentByDeployment($deployment);

            $statuses[] = ['deployment' => $deployment, 'push' => $push];
        }

        return $this->withTemplate($request, $response, $this->template, [
            'build' => $build,
            'selected' => $request->getQueryParams()['deployment'] ?? '',
            'statuses' => $statuses,

            'views' => $views,
            'selected_view' => $selectedView
        ]);
    }
}
