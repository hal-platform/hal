<?php
/**
 * @copyright ©2015 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Application\Pool;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use QL\Hal\Core\Entity\DeploymentView;
use QL\Hal\Flasher;
use QL\Hal\Utility\ValidatorTrait;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use Slim\Http\Request;

class EditViewController implements ControllerInterface
{
    use ValidatorTrait;

    const SUCCESS = 'Deployment View updated successfully.';

    /**
     * @type TemplateInterface
     */
    private $template;

    /**
     * @type Request
     */
    private $request;

    /**
     * @type Flasher
     */
    private $flasher;

    /**
     * @type EntityManagerInterface
     */
    private $em;

    /**
     * @type EntityRepository
     */
    private $viewRepo;

    /**
     * @type DeploymentView
     */
    private $view;

    /**
     * @type array
     */
    private $errors;

    /**
     * @param TemplateInterface $template
     * @param Request $request
     * @param Flasher $flasher
     * @param EntityManagerInterface $em
     * @param DeploymentView $view
     */
    public function __construct(
        TemplateInterface $template,
        Request $request,
        Flasher $flasher,
        EntityManagerInterface $em,
        DeploymentView $view
    ) {
        $this->template = $template;
        $this->request = $request;
        $this->flasher = $flasher;

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
