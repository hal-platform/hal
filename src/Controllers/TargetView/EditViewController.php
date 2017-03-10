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
use QL\Hal\Core\Entity\Application;
use QL\Hal\Core\Entity\DeploymentView;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use QL\Panthor\Utility\URI;

class EditViewController implements ControllerInterface
{
    use RedirectableControllerTrait;
    use SessionTrait;
    use TemplatedControllerTrait;
    use ValidatorTrait;

    const MSG_SUCCESS = 'Target View updated successfully.';

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
    private $viewRepo;

    /**
     * @var PoolService
     */
    private $poolService;

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
        $this->viewRepo = $em->getRepository(DeploymentView::class);

        $this->poolService = $poolService;
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

        $form = $this->getFormData($request, $view);

        if ($modified = $this->handleForm($form, $request, $view)) {
            $this->withFlash($request, Flash::SUCCESS, self::MSG_SUCCESS);
            return $this->withRedirectRoute($response, $this->uri, 'target_view', ['application' => $application->id(), 'view' => $view->id()]);
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
     * @return DeploymentView|null
     */
    private function handleForm(array $data, ServerRequestInterface $request, DeploymentView $view): ?DeploymentView
    {
        if ($request->getMethod() !== 'POST') {
            return null;
        }

        $view = $this->validateForm($view, ...array_values($data));

        if ($view) {
            // persist to database
            $this->em->merge($view);
            $this->em->flush();

            $this->poolService->clearViewCache($view);
        }

        return $view;
    }

    /**
     * @param DeploymentView $view
     * @param string $name
     * @param string $shared
     *
     * @return DeploymentView|null
     */
    private function validateForm(DeploymentView $view, $name, $shared)
    {
        $this->errors = $this->validateText($name, 'Name', 100, true);

        if ($this->errors) return;

        if ($name !== $view->name()) {

            $dupe = $this->viewRepo->findBy([
                'application' => $view->application(),
                'environment' => $view->environment(),
                'name' => $name
            ]);

            if ($dupe) {
                $this->errors[] = 'A Deployment view with this name already exists.';
            }
        }

        if ($this->errors) return;

        $view->withName($name);

        if ($shared && $view->user()) {
            $view->withUser(null);
        }

        return $view;
    }

    /**
     * @param ServerRequestInterface $request
     * @param DeploymentView $view
     *
     * @return array
     */
    private function getFormData(ServerRequestInterface $request, DeploymentView $view)
    {
        if ($request->getMethod() === 'POST') {
            $name = $request->getParsedBody()['name'] ?? '';
            $shared = $request->getParsedBody()['shared'] ?? '';

            $form = [
                'name' => trim($name),
                'shared' => ($shared === '1')
            ];

        } else {
            $form = [
                'name' => $view->name(),
                'shared' => ($view->user() === null),
            ];
        }

        return $form;
    }
}
