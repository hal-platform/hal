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
use QL\Hal\Core\Entity\DeploymentPool;
use QL\Hal\Core\Entity\DeploymentView;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use Slim\Http\Request;

class EditPoolController implements ControllerInterface
{
    use ValidatorTrait;

    const SUCCESS = 'Deployment Pool updated successfully.';

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
    private $poolRepo;

    /**
     * @var DeploymentView
     */
    private $view;

    /**
     * @var DeploymentPool
     */
    private $pool;

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
     * @param DeploymentPool $pool
     */
    public function __construct(
        TemplateInterface $template,
        Request $request,
        Flasher $flasher,
        PoolService $poolService,
        EntityManagerInterface $em,
        DeploymentView $view,
        DeploymentPool $pool
    ) {
        $this->template = $template;
        $this->request = $request;
        $this->flasher = $flasher;
        $this->poolService = $poolService;

        $this->em = $em;
        $this->poolRepo = $em->getRepository(DeploymentPool::CLASS);

        $this->view = $view;
        $this->pool = $pool;

        $this->errors = [];
    }

    /**
     * @inheritDoc
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

            'view' => $this->view,
            'pool' => $this->pool,
        ]);
    }

    /**
     * @param array $data
     *
     * @return DeploymentPool|null
     */
    private function handleForm(array $data)
    {
        if (!$this->request->isPost()) {
            return null;
        }

        if ($pool = $this->validateForm($data['name'])) {
            // persist to database
            $this->em->merge($pool);
            $this->em->flush();

            $this->poolService->clearViewCache($this->view);
        }

        return $pool;
    }

    /**
     * @param string $name
     *
     * @return DeploymentPool|null
     */
    private function validateForm($name)
    {
        $this->errors = $this->validateText($name, 'Name', 100, true);

        if ($this->errors) return;


        if ($name !== $this->pool->name()) {

            $dupe = $this->poolRepo->findBy([
                'view' => $this->view,
                'name' => $name
            ]);

            if ($dupe) {
                $this->errors[] = 'A Deployment pool with this name already exists.';
            }
        }

        if ($this->errors) return;

        return $this->pool->withName($name);
    }

    /**
     * @return array
     */
    private function data()
    {
        if ($this->request->isPost()) {
            $form = [
                'name' => trim($this->request->post('name')),
            ];
        } else {
            $form = [
                'name' => $this->pool->name(),
            ];
        }

        return $form;
    }
}
