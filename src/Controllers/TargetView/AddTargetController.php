<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\TargetView;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Hal\UI\Controllers\APITrait;
use Hal\UI\Controllers\RedirectableControllerTrait;
use Hal\UI\Controllers\SessionTrait;
use Hal\UI\Flash;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Hal\Core\Entity\Deployment;
use QL\Hal\Core\Entity\DeploymentPool;
use QL\Hal\Core\Entity\DeploymentView;
use QL\Hal\Core\Repository\DeploymentPoolRepository;
use QL\Panthor\ControllerInterface;
use QL\Panthor\Utility\JSON;
use QL\Panthor\Utility\URI;

class AddTargetController implements ControllerInterface
{
    use APITrait;
    use RedirectableControllerTrait;
    use SessionTrait;

    const MSG_SUCCESS = 'Deployment "%s" added.';
    const ERROR = 'Deployment could not be assigned.';

    const ERR_MISSING = 'Deployment must be specified.';
    const ERR_NOT_FOUND = 'Deployment not found.';
    const ERR_DUPE = 'Deployment already attached.';
    const ERR_DUPE_POOL = 'Deployment already attached to another pool.';

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var EntityRepository
     */
    private $deploymentRepo;

    /**
     * @var DeploymentPoolRepository
     */
    private $poolRepo;

    /**
     * @var JSON
     */
    private $json;

    /**
     * @var URI
     */
    private $uri;

    /**
     * @var array
     */
    private $errors;

    /**
     * @param EntityManagerInterface $em
     * @param Json $json
     * @param URI $uri
     */
    public function __construct(EntityManagerInterface $em, JSON $json, URI $uri)
    {
        $this->em = $em;
        $this->deploymentRepo = $em->getRepository(Deployment::class);
        $this->poolRepo = $em->getRepository(DeploymentPool::class);

        $this->json = $json;
        $this->uri = $uri;

        $this->errors = [];
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $application = $request->getAttribute(Application::class);
        $view = $request->getAttribute(DeploymentView::class);
        $pool = $request->getAttribute(DeploymentPool::class);

        $form = $this->getFormData($request);

        $target = $this->handleForm($pool, $form);

        if (!$target) {
            if ($this->isXHR($request)) {
                return $this
                    ->withNewBody($response, $this->json->encode(['errors' => $this->errors]))
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus(400);

            } else {
                $this->withFlash($request, Flash::ERROR, self::ERROR, implode(' ', $this->errors));
                return $this->withRedirectRoute(
                    $response,
                    $this->uri,
                    'target_view',
                    ['application' => $application->id(), 'view' => $view->id()]
                );
            }
        }

        $pool->deployments()->add($target);
        $this->em->merge($pool);
        $this->em->flush();

        if ($this->isXHR($request)) {

            $payload = [
                'deployment' => $target,
                'server' => $target->server(),
                'remove_url' => $this->uri->uriFor('target_pool_target.remove', [
                    'application' => $application,
                    'view' => $view->id(),
                    'pool' => $pool->id(),
                    'target' => $target->id()
                ])
            ];

            return $this
                ->withNewBody($response, $this->json->encode($payload))
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(200);

        } else {
            $this->withFlash($request, Flash::SUCCESS, sprintf(self::MSG_SUCCESS, $target->server()->formatPretty()));
            return $this->withRedirectRoute(
                $response,
                $this->uri,
                'target_view',
                ['application' => $application->id(), 'view' => $view->id()]
            );
        }
    }

    /**
     * @param DeploymentPool $pool
     * @param array $data
     *
     * @return Deployment|null
     */
    private function handleForm(DeploymentPool $pool, array $data)
    {
        if (!$data['target']) {
            $this->errors[] = self::ERR_MISSING;
        }

        if ($this->errors) return;

        $deployment = $this->deploymentRepo->find($data['target']);
        if (!$deployment) {
            $this->errors[] = self::ERR_NOT_FOUND;
        }

        if ($this->errors) return;

        // local dupe
        if ($pool->deployments()->contains($deployment)) {
            $this->errors[] = self::ERR_DUPE;
        }

        if ($this->errors) return;

        // foreign dupe
        $dupe = $this->poolRepo->getPoolForViewAndDeployment($pool->view(), $deployment);
        if ($dupe) {
            $this->errors[] = self::ERR_DUPE_POOL;
        }

        if ($this->errors) return;

        return $deployment;
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return array
     */
    private function getFormData(ServerRequestInterface $request)
    {
         $form = [
            'target' => $request->getParsedBody()['deployment'] ?? ''
        ];

        return $form;
    }
}
