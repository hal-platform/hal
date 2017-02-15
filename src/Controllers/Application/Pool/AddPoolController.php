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

class AddPoolController implements ControllerInterface
{
    use ValidatorTrait;

    const SUCCESS = 'Pool added successfully.';

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
     * @var callable
     */
    private $random;

    /**
     * @var EntityRepository
     */
    private $poolRepo;

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
     * @param callable $random
     * @param EntityManagerInterface $em
     * @param DeploymentView $view
     */
    public function __construct(
        TemplateInterface $template,
        Request $request,
        Flasher $flasher,
        PoolService $poolService,
        callable $random,
        EntityManagerInterface $em,
        DeploymentView $view
    ) {
        $this->template = $template;
        $this->request = $request;
        $this->flasher = $flasher;
        $this->poolService = $poolService;

        $this->random = $random;

        $this->em = $em;
        $this->poolRepo = $em->getRepository(DeploymentPool::CLASS);

        $this->view = $view;

        $this->errors = [];
    }

    /**
     * @inheritDoc
     */
    public function __invoke()
    {
        $form = $this->data();

        if ($pool = $this->handleForm($form)) {
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
     * @return DeploymentPool|null
     */
    private function handleForm(array $data)
    {
        if (!$this->request->isPost()) {
            return null;
        }

        $pool = $this->validateForm($data['name']);

        if ($pool) {
            // persist to database
            $this->em->persist($pool);
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
        $this->errors = $this->validateText($name, 'Pool name', 100, true);

        if ($this->errors) return;

        $dupe = $this->poolRepo->findBy([
            'view' => $this->view,
            'name' => $name
        ]);

        if ($dupe) {
            $this->errors[] = 'A Deployment pool with this name already exists.';
        }

        if ($this->errors) return;

        $id = call_user_func($this->random);
        $pool = (new DeploymentPool($id))
            ->withName($name)
            ->withView($this->view);

        return $pool;
    }

    /**
     * @return array
     */
    private function data()
    {
        $form = [
            'name' => trim($this->request->post('name')),
        ];

        return $form;
    }
}
