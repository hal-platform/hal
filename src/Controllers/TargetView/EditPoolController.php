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
use Hal\UI\Controllers\TemplatedControllerTrait;
use Hal\UI\Flash;
use Hal\UI\Service\PoolService;
use Hal\UI\Utility\ValidatorTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Hal\Core\Entity\DeploymentPool;
use QL\Hal\Core\Entity\DeploymentView;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use QL\Panthor\Utility\URI;

class EditPoolController implements ControllerInterface
{
    use RedirectableControllerTrait;
    use SessionTrait;
    use TemplatedControllerTrait;
    use ValidatorTrait;

    const MSG_SUCCESS = 'Target Pool updated successfully.';

    /**
     * @var TemplateInterface
     */
    private $template;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var EntityRepository
     */
    private $poolRepo;

    /**
     * @var PoolService
     */
    private $poolService;

    /**
     * @var URI
     */
    private $uri;

    /**
     * @var array
     */
    private $errors;

    /**
     * @param TemplateInterface $template
     * @param EntityManagerInterface $em
     * @param PoolService $poolService
     * @param URI $uri
     */
    public function __construct(
        TemplateInterface $template,
        EntityManagerInterface $em,
        PoolService $poolService,
        URI $uri
    ) {
        $this->template = $template;

        $this->em = $em;
        $this->poolRepo = $em->getRepository(DeploymentPool::class);
        $this->poolService = $poolService;

        $this->uri = $uri;

        $this->errors = [];
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $view = $request->getAttribute(DeploymentView::class);
        $pool = $request->getAttribute(DeploymentPool::class);

        $form = $this->getFormData($request, $pool);

        if ($modified = $this->handleForm($form, $request)) {
            $this->withFlash($request, Flash::SUCCESS, self::MSG_SUCCESS);
            return $this->withRedirectRoute(
                $response,
                $this->uri,
                'target_view',
                ['application' => $view->application()->id(), 'view' => $view->id()]
            );
        }

        return $this->withTemplate($request, $response, $this->template, [
            'form' => $form,
            'errors' => $this->errors,

            'application' => $view->application(),
            'environment' => $view->environment(),

            'view' => $view,
            'pool' => $pool,
        ]);
    }

    /**
     * @param array $data
     * @param ServerRequestInterface $request
     *
     * @return DeploymentPool|null
     */
    private function handleForm(array $data, ServerRequestInterface $request): ?DeploymentPool
    {
        if ($request->getMethod() !== 'POST') {
            return null;
        }

        $pool = $request->getAttribute(DeploymentPool::class);

        $pool = $this->validateForm($pool, $data['name']);

        if ($pool) {
            // persist to database
            $this->em->merge($pool);
            $this->em->flush();

            $this->poolService->clearViewCache($pool->view());
        }

        return $pool;
    }

    /**
     * @param DeploymentPool $pool
     * @param string $name
     *
     * @return DeploymentPool|null
     */
    private function validateForm(DeploymentPool $pool, $name)
    {
        $this->errors = $this->validateText($name, 'Name', 100, true);

        if ($this->errors) return;

        if ($name !== $pool->name()) {

            $dupe = $this->poolRepo->findBy([
                'view' => $pool->view(),
                'name' => $name
            ]);

            if ($dupe) {
                $this->errors[] = 'A Deployment pool with this name already exists.';
            }
        }

        if ($this->errors) return;

        return $pool->withName($name);
    }

    /**
     * @param ServerRequestInterface $request
     * @param DeploymentPool $pool
     *
     * @return array
     */
    private function getFormData(ServerRequestInterface $request, DeploymentPool $pool)
    {
        if ($request->getMethod() === 'POST') {
            $name = $request->getParsedBody()['name'] ?? '';

            $form = [
                'name' => trim($name)
            ];

        } else {
            $form = [
                'name' => $pool->name()
            ];
        }

        return $form;
    }
}
