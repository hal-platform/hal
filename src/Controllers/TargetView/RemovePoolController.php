<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\TargetView;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Hal\UI\Controllers\RedirectableControllerTrait;
use Hal\UI\Controllers\SessionTrait;
use Hal\UI\Flash;
use Hal\UI\Service\PoolService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Hal\Core\Entity\Application;
use QL\Hal\Core\Entity\DeploymentPool;
use QL\Panthor\ControllerInterface;
use QL\Panthor\Utility\URI;

class RemovePoolController implements ControllerInterface
{
    use RedirectableControllerTrait;
    use SessionTrait;

    const MSG_SUCCESS = 'Target Pool "%s" removed.';

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
     * @param PoolService $poolService
     * @param URI $uri
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
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $application = $request->getAttribute(Application::class);
        $pool = $request->getAttribute(DeploymentPool::class);

        $this->em->remove($pool);
        $this->em->flush();

        $this->poolService->clearViewCache($pool->view());

        $this->withFlash($request, Flash::SUCCESS, sprintf(self::MSG_SUCCESS, $pool->name()));
        return $this->withRedirectRoute(
            $response,
            $this->uri,
            'target_view',
            ['application' => $application->id(), 'view' => $pool->view()->id()]
        );
    }
}
