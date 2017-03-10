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

class AddPoolController implements ControllerInterface
{
    use RedirectableControllerTrait;
    use SessionTrait;
    use TemplatedControllerTrait;
    use ValidatorTrait;

    const MSG_SUCCESS = 'Pool added successfully.';

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
     * @var callable
     */
    private $random;

    /**
     * @var array
     */
    private $errors;

    /**
     * @param TemplateInterface $template
     * @param EntityManagerInterface $em
     * @param PoolService $poolService
     * @param URI $uri
     * @param callable $random
     */
    public function __construct(
        TemplateInterface $template,
        EntityManagerInterface $em,
        PoolService $poolService,
        URI $uri,
        callable $random
    ) {
        $this->template = $template;

        $this->em = $em;
        $this->poolRepo = $em->getRepository(DeploymentPool::class);
        $this->poolService = $poolService;

        $this->uri = $uri;
        $this->random = $random;

        $this->errors = [];
    }

    /**
     * @inheritdoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $view = $request->getAttribute(DeploymentView::class);

        $form = $this->getFormData($request);

        if ($pool = $this->handleForm($form, $request)) {
            $this->poolService->clearViewCache($view);

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

            'view' => $view
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

        $view = $request->getAttribute(DeploymentView::class);

        $pool = $this->validateForm($view, $data['name']);

        if ($pool) {
            // persist to database
            $this->em->persist($pool);
            $this->em->flush();
        }

        return $pool;
    }

    /**
     * @param DeploymentView $view
     * @param string $name
     *
     * @return DeploymentPool|null
     */
    private function validateForm(DeploymentView $view, $name)
    {
        $this->errors = $this->validateText($name, 'Pool name', 100, true);

        if ($this->errors) return;

        $dupe = $this->poolRepo->findBy([
            'view' => $view,
            'name' => $name
        ]);

        if ($dupe) {
            $this->errors[] = 'A target pool with this name already exists.';
        }

        if ($this->errors) return;

        $id = call_user_func($this->random);
        $pool = (new DeploymentPool($id))
            ->withName($name)
            ->withView($view);

        return $pool;
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return array
     */
    private function getFormData(ServerRequestInterface $request)
    {
        $name = $request->getParsedBody()['name'] ?? '';

        $form = [
            'name' => trim($name)
        ];

        return $form;
    }
}
