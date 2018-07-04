<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Release;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Hal\Core\Entity\Application;
use Hal\Core\Entity\Environment;
use Hal\Core\Entity\JobType\Build;
use Hal\Core\Entity\Target;
use Hal\Core\Repository\EnvironmentRepository;
use Hal\UI\Controllers\RedirectableControllerTrait;
use Hal\UI\Controllers\SessionTrait;
use Hal\UI\Controllers\TemplatedControllerTrait;
use Hal\UI\Security\UserAuthorizations;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use QL\Panthor\Utility\URI;

class StartReleaseController implements ControllerInterface
{
    use RedirectableControllerTrait;
    use SessionTrait;
    use TemplatedControllerTrait;

    private const ERR_NOT_BUILDABLE = 'This build cannot be deployed.';

    /**
     * @var TemplateInterface
     */
    private $template;

    /**
     * @var EntityRepository
     */
    private $targetRepo;

    /**
     * @var EnvironmentRepository
     */
    private $environmentRepo;

    /**
     * @var URI
     */
    private $uri;

    /**
     * @param TemplateInterface $template
     * @param EntityManagerInterface $em
     * @param URI $uri
     */
    public function __construct(
        TemplateInterface $template,
        EntityManagerInterface $em,
        URI $uri
    ) {
        $this->template = $template;
        $this->targetRepo = $em->getRepository(Target::class);
        $this->environmentRepo = $em->getRepository(Environment::class);

        $this->uri = $uri;
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $build = $request->getAttribute(Build::class);
        $authorizations = $this->getAuthorizations($request);

        $selectedEnvironment = $request->getAttribute(DeployMiddleware::SELECTED_ENVIRONMENT_ATTRIBUTE);

        if (!$build->isSuccess()) {
            $this->withFlashError($request, self::ERR_NOT_BUILDABLE);
            return $this->withRedirectRoute($response, $this->uri, 'build', ['build' => $build->id()]);
        }

        $environments = $targets = [];
        $deployableTargets = 0;

        if ($selectedEnvironment) {
            $targets = $this->targetRepo->findBy(['application' => $build->application(), 'environment' => $selectedEnvironment]);
            $deployableTargets = $this->deployableTargets($authorizations, $build->application(), $selectedEnvironment, $targets);

        } else {
            $environments = $this->environmentRepo->getBuildableEnvironmentsByApplication($build->application());
        }

        return $this->withTemplate($request, $response, $this->template, [
            'application' => $build->application(),
            'build' => $build,

            'environments' => $environments,
            'targets' => $targets,
            'deployable_targets' => $deployableTargets,
        ]);
    }

    /**
     * @param UserAuthorizations $authorizations
     * @param Application $application
     * @param Environment $environment
     * @param array $targets
     *
     * @return int
     */
    private function deployableTargets(UserAuthorizations $authorizations, Application $application, Environment $environment, array $targets)
    {
        $deployables = 0;

        if (!$authorizations->canDeploy($application, $environment)) {
            return $deployables;
        }

        foreach ($targets as $target) {
            if ($target->isAWS() && !$target->credential()) {
                continue;
            }

            if ($target->lastJob() && $target->lastJob()->inProgress()) {
                continue;
            }

            $deployables++;
        }

        return $deployables;
    }
}
