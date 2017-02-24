<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Push;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Hal\UI\Service\PoolService;
use QL\Hal\Core\Entity\Build;
use QL\Hal\Core\Entity\Deployment;
use QL\Hal\Core\Entity\Push;
use QL\Hal\Core\Repository\PushRepository;
use QL\Panthor\Slim\NotFound;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use Slim\Http\Request;

class StartPushController implements ControllerInterface
{
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
     * @var Request
     */
    private $request;

    /**
     * @var NotFound
     */
    private $notFound;

    /**
     * @var array
     */
    private $parameters;

    /**
     * @param TemplateInterface $template
     * @param EntityManagerInterface $em
     * @param Request $request
     * @param NotFound $notFound
     * @param PoolService $poolService
     * @param array $parameters
     */
    public function __construct(
        TemplateInterface $template,
        EntityManagerInterface $em,
        PoolService $poolService,
        Request $request,
        NotFound $notFound,
        array $parameters
    ) {
        $this->template = $template;

        $this->buildRepo = $em->getRepository(Build::CLASS);
        $this->pushRepo = $em->getRepository(Push::CLASS);
        $this->deploymentRepo = $em->getRepository(Deployment::CLASS);

        $this->poolService = $poolService;
        $this->request = $request;
        $this->notFound = $notFound;
        $this->parameters = $parameters;
    }

    /**
     * @inheritDoc
     */
    public function __invoke()
    {
        $build = $this->buildRepo->find($this->parameters['build']);

        if (!$build || $build->status() != 'Success') {
            return call_user_func($this->notFound);
        }

        // Get selected view user has saved
        $views = $this->poolService->getViews($build->application(), $build->environment());
        $selectedView = $this->poolService->findSelectedView($build->application(), $build->environment(), $views);

        $deployments = $this->deploymentRepo->getDeploymentsByApplicationEnvironment($build->application(), $build->environment());
        $statuses = [];
        foreach ($deployments as $deployment) {
            $push = $this->pushRepo->getMostRecentByDeployment($deployment);

            $statuses[] = compact('deployment', 'push');
        }

        $this->template->render([
            'build' => $build,
            'selected' => $this->request->get('deployment'),
            'statuses' => $statuses,

            'views' => $views,
            'selected_view' => $selectedView
        ]);
    }
}