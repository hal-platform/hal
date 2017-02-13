<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Application\Pool;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Hal\UI\Flasher;
use Hal\UI\Service\PoolService;
use Hal\UI\Utility\ValidatorTrait;
use QL\Hal\Core\Entity\DeploymentView;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use Slim\Http\Request;

class EditViewController implements ControllerInterface
{
    use ValidatorTrait;

    const SUCCESS = 'Deployment View updated successfully.';

    /**
     * @var TemplateInterface
     */
    private $template;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var Flasher
     */
    private $flasher;

    /**
     * @var PoolService
     */
    private $poolService;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var EntityRepository
     */
    private $viewRepo;

    /**
     * @var DeploymentView
     */
    private $view;

    /**
     * @var array
     */
    private $errors;

    /**
     * @param TemplateInterface $template
     * @param Request $request
     * @param Flasher $flasher
     * @param PoolService $poolService
     * @param EntityManagerInterface $em
     * @param DeploymentView $view
     */
    public function __construct(
        TemplateInterface $template,
        Request $request,
        Flasher $flasher,
        PoolService $poolService,
        EntityManagerInterface $em,
        DeploymentView $view
    ) {
        $this->template = $template;
        $this->request = $request;
        $this->flasher = $flasher;
        $this->poolService = $poolService;

        $this->em = $em;
        $this->viewRepo = $em->getRepository(DeploymentView::CLASS);

        $this->view = $view;

        $this->errors = [];
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        $form = $this->data();

        if ($modified = $this->handleForm($form)) {
            return $this->flasher
                ->withFlash(self::SUCCESS, 'success')
                ->load('deployment_view', ['view' => $this->view->id()]);
        }

        $this->template->render([
            'form' => $form,
            'errors' => $this->errors,

            'application' => $this->view->application(),
            'environment' => $this->view->environment(),

            'view' => $this->view
        ]);
    }

    /**
     * @param array $data
     *
     * @return DeploymentView|null
     */
    private function handleForm(array $data)
    {
        if (!$this->request->isPost()) {
            return null;
        }

        if ($view = $this->validateForm($data['name'], $data['shared'])) {
            // persist to database
            $this->em->merge($view);
            $this->em->flush();

            $this->poolService->clearViewCache($this->view);
        }

        return $view;
    }

    /**
     * @param string $name
     * @param string $shared
     *
     * @return DeploymentView|null
     */
    private function validateForm($name, $shared)
    {
        $this->errors = $this->validateText($name, 'Name', 100, true);

        $shared = ($shared === '1');

        if ($this->errors) return;

        if ($name !== $this->view->name()) {

            $dupe = $this->viewRepo->findBy([
                'application' => $this->view->application(),
                'environment' => $this->view->environment(),
                'name' => $name
            ]);

            if ($dupe) {
                $this->errors[] = 'A Deployment view with this name already exists.';
            }
        }

        if ($this->errors) return;

        $this->view
            ->withName($name);

        if ($shared && $this->view->user()) {
            $this->view
                ->withUser(null);
        }

        return $this->view;
    }

    /**
     * @return array
     */
    private function data()
    {
        if ($this->request->isPost()) {
            $form = [
                'name' => trim($this->request->post('name')),
                'shared' => $this->request->post('shared'),
            ];
        } else {
            $form = [
                'name' => $this->view->name(),
                'shared' => ($this->view->user() === null) ? '1' : '',
            ];
        }

        return $form;
    }
}
