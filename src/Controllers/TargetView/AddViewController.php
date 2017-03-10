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
use QL\Hal\Core\Entity\Environment;
use QL\Hal\Core\Entity\User;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use QL\Panthor\Utility\URI;

class AddViewController implements ControllerInterface
{
    use RedirectableControllerTrait;
    use SessionTrait;
    use TemplatedControllerTrait;
    use ValidatorTrait;

    const MSG_SUCCESS = 'View added successfully.';

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
        $this->viewRepo = $em->getRepository(DeploymentView::class);
        $this->poolService = $poolService;

        $this->uri = $uri;
        $this->random = $random;

        $this->errors = [];
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $application = $request->getAttribute(Application::class);
        $environment = $request->getAttribute(Environment::class);

        $form = $this->getFormData($request);

        if ($view = $this->handleForm($form, $request)) {
            $this->poolService->clearCache($application, $environment);

            $this->withFlash($request, Flash::SUCCESS, self::MSG_SUCCESS);
            return $this->withRedirectRoute(
                $response,
                $this->uri,
                'target_views',
                ['application' => $application->id(), 'environment' => $environment->id()]
            );
        }

        return $this->withTemplate($request, $response, $this->template, [
            'form' => $form,
            'errors' => $this->errors,

            'application' => $application,
            'environment' => $environment
        ]);
    }

    /**
     * @param array $data
     * @param ServerRequestInterface $request
     *
     * @return DeploymentView|null
     */
    private function handleForm(array $data, ServerRequestInterface $request): ?DeploymentView
    {
        if ($request->getMethod() !== 'POST') {
            return null;
        }

        $application = $request->getAttribute(Application::class);
        $environment = $request->getAttribute(Environment::class);
        $user = $this->getUser($request);

        $view = $this->validateForm($application, $environment, $user, ...array_values($data));

        if ($view) {
            // persist to database
            $this->em->persist($view);
            $this->em->flush();
        }

        return $view;
    }

    /**
     * @param Application $app
     * @param Environment $env
     * @param User $user
     * @param string $name
     * @param bool $isShared
     *
     * @return DeploymentView|null
     */
    private function validateForm(Application $application, Environment $environment, User $user, $name, $isShared)
    {
        $this->errors = $this->validateText($name, 'Name', 100, true);

        if ($this->errors) return;

        $dupe = $this->viewRepo->findBy([
            'application' => $application,
            'environment' => $environment,
            'name' => $name
        ]);

        if ($dupe) {
            $this->errors[] = 'A Target View with this name already exists.';
        }

        if ($this->errors) return;

        $id = call_user_func($this->random);
        $view = (new DeploymentView($id))
            ->withName($name)
            ->withApplication($application)
            ->withEnvironment($environment);

        if (!$isShared) {
            $view->withUser($user);
        }

        return $view;
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return array
     */
    private function getFormData(ServerRequestInterface $request)
    {
        $name = $request->getParsedBody()['name'] ?? '';
        $shared = $request->getParsedBody()['shared'] ?? '';

        $form = [
            'name' => trim($name),
            'shared' => ($shared === '1')
        ];

        return $form;
    }
}
