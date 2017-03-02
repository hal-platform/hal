<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Application;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Hal\UI\Controllers\RedirectableControllerTrait;
use Hal\UI\Service\StickyEnvironmentService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Hal\Core\Entity\Application;
use QL\Hal\Core\Entity\Environment;
use QL\Panthor\MiddlewareInterface;
use QL\Panthor\Utility\URI;

class DashboardStickyEnvironmentHandler implements MiddlewareInterface
{
    use RedirectableControllerTrait;

    /**
     * @var EntityRepository
     */
    private $envRepo;

    /**
     * @var StickyEnvironmentService
     */
    private $service;

    /**
     * @var URI
     */
    private $uri;

    /**
     * @param EntityManagerInterface $em
     * @param StickyEnvironmentService $service
     * @param URI $uri
     */
    public function __construct(
        EntityManagerInterface $em,
        StickyEnvironmentService $service,
        URI $uri
    ) {
        $this->envRepo = $em->getRepository(Environment::class);
        $this->uri = $uri;
        $this->service = $service;
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        $application = $request->getAttribute(Application::class);
        $environmentID = $request->getQueryParams()['environment'] ?? '';

        // Fall through to controller if no environment in query string
        if (!$environmentID) {
            return $next($request, $response);
        }

        // environment is valid. save to cookie.
        if ($environment = $this->envRepo->find($environmentID)) {
            $response = $this->service->save($request, $response, $application->id(), $environment->id());
        }

        return $this->withRedirectRoute($response, $this->uri, 'application.dashboard', ['application' => $application->id()]);
    }
}
