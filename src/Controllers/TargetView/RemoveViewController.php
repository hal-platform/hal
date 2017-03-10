<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\TargetView;

use Doctrine\ORM\EntityManagerInterface;
use Hal\UI\Controllers\RedirectableControllerTrait;
use Hal\UI\Controllers\SessionTrait;
use Hal\UI\Flash;
use Hal\UI\Service\PoolService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Hal\Core\Entity\DeploymentView;
use QL\Panthor\ControllerInterface;
use QL\Panthor\Utility\URI;

class RemoveViewController implements ControllerInterface
{
    use RedirectableControllerTrait;
    use SessionTrait;

    const MSG_SUCCESS = 'Target View "%s" removed.';

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var PoolService
     */
    private $poolService;

    /**
     * @var URI
     */
    private $uri;

    /**
     * @param EntityManagerInterface $em
     * @param Flasher $flasher
     * @param DeploymentView $view
     */
    public function __construct(
        EntityManagerInterface $em,
        PoolService $poolService,
        URI $uri
    ) {
        $this->em = $em;
        $this->poolService = $poolService;
        $this->uri = $uri;
    }

    /**
     * @inheritdoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $view = $request->getAttribute(DeploymentView::class);

        $application = $view->application();
        $environment = $view->environment();

        $this->em->remove($view);
        $this->em->flush();

        $this->poolService->clearViewCache($view);

        $this->withFlash($request, Flash::SUCCESS, sprintf(self::MSG_SUCCESS, $view->name()));
        return $this->withRedirectRoute(
            $response,
            $this->uri,
            'target_views',
            [
                'application' => $application->id(),
                'environment' => $environment->id()
            ]
        );
    }
}
