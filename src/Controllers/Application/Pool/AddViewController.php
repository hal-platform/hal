<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Hal\Controllers\Application\Pool;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use QL\Hal\Core\Entity\Application;
use QL\Hal\Core\Entity\Environment;
use QL\Hal\Core\Entity\DeploymentView;
use QL\Hal\Core\Entity\User;
use QL\Hal\Flasher;
use QL\Hal\Service\PoolService;
use QL\Hal\Utility\ValidatorTrait;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use Slim\Http\Request;

class AddViewController implements ControllerInterface
{
    use ValidatorTrait;

    const SUCCESS = 'View added successfully.';

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
     * @var callable
     */
    private $random;

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
     * @var Application
     */
    private $application;

    /**
     * @var Environment
     */
    private $environment;

    /**
     * @var User
     */
    private $currentUser;

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
     * @param Application $application
     * @param Environment $environment
     * @param User $currentUser
     */
    public function __construct(
        TemplateInterface $template,
        Request $request,
        Flasher $flasher,
        PoolService $poolService,
        callable $random,
        EntityManagerInterface $em,
        Application $application,
        Environment $environment,
        User $currentUser
    ) {
        $this->template = $template;
        $this->request = $request;
        $this->flasher = $flasher;
        $this->poolService = $poolService;

        $this->random = $random;

        $this->em = $em;
        $this->viewRepo = $em->getRepository(DeploymentView::CLASS);

        $this->application = $application;
        $this->environment = $environment;
        $this->currentUser = $currentUser;

        $this->errors = [];
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        $form = $this->data();

        if ($pool = $this->handleForm($form)) {
            return $this->flasher
                ->withFlash(self::SUCCESS, 'success')
                ->load('pools', ['application' => $this->application->id(), 'environment' => $this->environment->id()]);
        }

        $this->template->render([
            'form' => $form,
            'errors' => $this->errors,

            'application' => $this->application,
            'environment' => $this->environment
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

        $pool = $this->validateForm($data['name'], $data['shared']);

        if ($pool) {
            // persist to database
            $this->em->persist($pool);
            $this->em->flush();

            $this->poolService->clearCache($this->application, $this->environment);
        }

        return $pool;
    }

    /**
     * @param string $name
     * @param bool $isShared
     *
     * @return DeploymentPool|null
     */
    private function validateForm($name, $isShared)
    {
        $this->errors = $this->validateText($name, 'Name', 100, true);

        if ($this->errors) return;

        $dupe = $this->viewRepo->findBy([
            'application' => $this->application,
            'environment' => $this->environment,
            'name' => $name
        ]);

        if ($dupe) {
            $this->errors[] = 'A Deployment view with this name already exists.';
        }

        if ($this->errors) return;

        $id = call_user_func($this->random);
        $pool = (new DeploymentView($id))
            ->withName($name)
            ->withApplication($this->application)
            ->withEnvironment($this->environment);

        if (!$isShared) {
            $pool->withUser($this->currentUser);
        }

        return $pool;
    }

    /**
     * @return array
     */
    private function data()
    {
        $form = [
            'name' => trim($this->request->post('name')),
            'shared' => ($this->request->post('shared') === '1')
        ];

        return $form;
    }
}
