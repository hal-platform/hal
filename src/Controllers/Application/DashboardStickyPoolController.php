<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Application;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Hal\UI\Controllers\APITrait;
use Hal\UI\Controllers\RedirectableControllerTrait;
use Hal\UI\Service\StickyPoolService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Hal\Core\Entity\Application;
use QL\Hal\Core\Entity\DeploymentView;
use QL\Hal\Core\Entity\Environment;
use QL\Panthor\ControllerInterface;
use QL\Panthor\Utility\JSON;
use QL\Panthor\Utility\URI;

class DashboardStickyPoolController implements ControllerInterface
{
    use APITrait;
    use RedirectableControllerTrait;

    /**
     * @var EntityRepository
     */
    private $viewRepo;

    /**
     * @var StickyPoolService
     */
    private $stickyPool;

    /**
     * @var JSON
     */
    private $json;

    /**
     * @var URI
     */
    private $uri;

    /**
     * @param EntityManagerInterface $em
     * @param StickyPoolService $stickyPool
     * @param JSON $json
     * @param URI $uri
     */
    public function __construct(
        EntityManagerInterface $em,
        StickyPoolService $stickyPool,
        JSON $json,
        URI $uri
    ) {
        $this->viewRepo = $em->getRepository(DeploymentView::class);
        $this->stickyPool = $stickyPool;

        $this->json = $json;
        $this->uri = $uri;
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $application = $request->getAttribute(Application::class);
        $environment = $request->getAttribute(Environment::class);

        $viewID = $request->getParsedBody()['view'] ?? '';
        $saved = $this->validateView($application, $environment, $viewID);

        $response = $this->stickyPool->save($request, $response, $application->id(), $environment->id(), $saved);

        if ($this->isXHR($request)) {
            return $this
                ->withNewBody($response, $this->json->encode(['awk' => 'cool story bro']))
                ->withHeader('Content-Type', 'application/json');
        }

        return $this->withRedirectRoute($response, $this->uri, 'application.dashboard', ['application' => $application->id()]);
    }

    /**
     * @param Application $application
     * @param Environment $environment
     * @param string $viewID
     *
     * @return string|null
     */
    private function validateView(Application $application, Environment $environment, $viewID): ?string
    {
        $saved = null;

        if ($viewID) {
            $view = $this->viewRepo->findOneBy([
                'id' => $viewID,
                'application' => $application,
                'environment' => $environment
            ]);

            $saved = $view->id();
        }

        return $saved;
    }
}
