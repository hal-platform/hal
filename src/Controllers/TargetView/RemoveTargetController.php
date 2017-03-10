<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\TargetView;

use Doctrine\ORM\EntityManagerInterface;
use Hal\UI\Controllers\APITrait;
use Hal\UI\Controllers\RedirectableControllerTrait;
use Hal\UI\Controllers\SessionTrait;
use Hal\UI\Flash;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Hal\Core\Entity\Deployment;
use QL\Hal\Core\Entity\DeploymentPool;
use QL\Panthor\ControllerInterface;
use QL\Panthor\Utility\URI;

class RemoveTargetController implements ControllerInterface
{
    use APITrait;
    use RedirectableControllerTrait;
    use SessionTrait;

    const MSG_SUCCESS = 'Target "%s" removed.';

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var URI
     */
    private $uri;

    /**
     * @param EntityManagerInterface $em
     * @param URI $uri
     */
    public function __construct(EntityManagerInterface $em, URI $uri)
    {
        $this->em = $em;
        $this->uri = $uri;
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $pool = $request->getAttribute(DeploymentPool::class);
        $target = $request->getAttribute(Deployment::class);

        $pool->deployments()->removeElement($target);

        $this->em->merge($pool);
        $this->em->flush();

        if ($this->isXHR($request)) {
            return $this
                ->withNewBody($response, '{}')
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(200);
        }

        $this->withFlash($request, Flash::SUCCESS, sprintf(self::SUCCESS, $target->server()->formatPretty()));
        return $this->withRedirectRoute(
            $response,
            $this->uri,
            'target_view',
            ['application' => $application->id(), 'view' => $pool->view()->id()]
        );
    }
}
