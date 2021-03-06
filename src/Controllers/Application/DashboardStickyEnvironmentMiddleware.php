<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Application;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Hal\Core\Entity\Application;
use Hal\Core\Entity\Environment;
use Hal\UI\Controllers\RedirectableControllerTrait;
use Hal\UI\Service\StickyEnvironmentService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Panthor\MiddlewareInterface;
use QL\Panthor\Utility\URI;

class DashboardStickyEnvironmentMiddleware implements MiddlewareInterface
{
    use RedirectableControllerTrait;

    /**
     * @var EntityRepository
     */
    private $environmentRepo;

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
        $this->environmentRepo = $em->getRepository(Environment::class);
        $this->uri = $uri;
        $this->service = $service;
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        $application = $request->getAttribute(Application::class);

        $params = $request->getQueryParams();

        // Fall through to controller if no environment in query string
        if (!isset($params['environment'])) {
            return $next($request, $response);
        }

        $environmentID = $params['environment'] ?? '';

        if ($environmentID) {
            // validate environment ID if environment is valid.
            $environment = $this->environmentRepo->find($environmentID);
            if ($environment instanceof Environment) {
                $environmentID = $environment->id();
            }
        }

        $response = $this->service->save($request, $response, $application->id(), $environmentID);

        return $this->withRedirectRoute($response, $this->uri, 'application.dashboard', ['application' => $application->id()]);
    }
}
