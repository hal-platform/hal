<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Build;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Hal\Core\Entity\Application;
use Hal\Core\Entity\Target;
use Hal\Core\Entity\Environment;
use Hal\Core\Entity\User;
use Hal\Core\Repository\EnvironmentRepository;
use Hal\UI\Controllers\SessionTrait;
use Hal\UI\Controllers\TemplatedControllerTrait;
use Hal\UI\Security\UserAuthorizations;
use Hal\UI\Service\StickyEnvironmentService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Hal\UI\VersionControl\BuildableRefs;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;

class StartBuildController implements ControllerInterface
{
    use SessionTrait;
    use TemplatedControllerTrait;

    /**
     * @var TemplateInterface
     */
    private $template;

    /**
     * @var EnvironmentRepository
     */
    private $environmentRepository;

    /**
     * @var EntityRepository
     */
    private $targetRepository;

    /**
     * @var BuildableRefs
     */
    private $vcs;

    /**
     * @var StickyEnvironmentService
     */
    private $stickyService;

    /**
     * @param TemplateInterface $template
     * @param EntityManagerInterface $em
     * @param BuildableRefs $vcs
     * @param StickyEnvironmentService $stickyService
     */
    public function __construct(
        TemplateInterface $template,
        EntityManagerInterface $em,
        BuildableRefs $vcs,
        StickyEnvironmentService $stickyService
    ) {
        $this->template = $template;
        $this->vcs = $vcs;
        $this->stickyService = $stickyService;

        $this->environmentRepository = $em->getRepository(Environment::class);
        $this->targetRepository = $em->getRepository(Target::class);
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $application = $request->getAttribute(Application::class);
        $user = $this->getUser($request);
        $userAuthorizations = $this->getAuthorizations($request);

        $form = $this->getFormData($request, $application);

        $refs = $this->vcs->getVCSData($application);
        $environments = $this->environmentRepository->getBuildableEnvironmentsByApplication($application);
        $targets = $this->getTargetStatusesForEnvironment($application, $userAuthorizations, $form['environment']);

        return $this->withTemplate($request, $response, $this->template, $refs + $targets + [
            'form' => $form,

            'application' => $application,
            'environments' => $environments,
        ]);
    }

    /**
     * @param ServerRequestInterface $request
     * @param Application $application
     *
     * @return array
     */
    private function getFormData(ServerRequestInterface $request, Application $application)
    {
        $data = $request->getParsedBody();

        // Automatically select an environment from sticky pref if this is fresh form
        $env = $data['environment'] ?? null;
        if ($env === null) {
            $env = $this->stickyService->get($request, $application);
        }

        return [
            'environment' => $env,
            'search' => $data['search'] ?? '',
            'reference' => $data['reference'] ?? '',
            'gitref' => $data['gitref'] ?? '',

            'metadata_names' => $data['metadata_names'] ?? [],
            'metadata_values' => $data['metadata_values'] ?? [],
        ];
    }

    /**
     * @param Application $application
     * @param UserAuthorizations $userAuthorizations
     * @param Environment|string $env
     *
     * @return array
     */
    public function getTargetStatusesForEnvironment(Application $application, UserAuthorizations $userAuthorizations, $env)
    {
        $environment = '';
        if ($env instanceof Environment) {
            $environment = $env;
        } elseif ($env === '!any') {
            $environment = null;
        } elseif ($env) {
            $environment = $this->environmentRepository->find($env);
        }

        if (!$environment) {
            return [
                'can_deploy' => false,
                'available_targets' => [],
            ];
        }

        $available = $this->targetRepository->findBy(['application' => $application, 'environment' => $environment]);

        $canPush = $userAuthorizations->canDeploy($application, $environment);

        return [
            'can_deploy' => $canPush,
            'available_targets' => $available,
        ];
    }
}
