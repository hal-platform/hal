<?php
/**
 * @copyright (c) 2017 Quicken Loans Inc.
 *
 * Forfull license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Release;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Hal\Core\Entity\Application;
use Hal\Core\Entity\Build;
use Hal\Core\Entity\Environment;
use Hal\Core\Entity\Target;
use Hal\UI\Controllers\TemplatedControllerTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Panthor\MiddlewareInterface;
use QL\Panthor\TemplateInterface;

class SelectEnvironmentMiddleware implements MiddlewareInterface
{
    use TemplatedControllerTrait;

    public const SELECTED_ENVIRONMENT_ATTRIBUTE = 'selected_environment';

    /**
     * @var TemplateInterface
     */
    private $template;

    /**
     * @var EntityRepository
     */
    private $environmentRepository;
    private $targetRepository;

    /**
     * @param TemplateInterface $template
     * @param EntityManagerInterface $em
     */
    public function __construct(TemplateInterface $template, EntityManagerInterface $em)
    {
        $this->template = $template;
        $this->environmentRepository = $em->getRepository(Environment::class);
        $this->targetRepository = $em->getRepository(Target::class);
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        $build = $request->getAttribute(Build::class);

        if (!$build->isSuccess()) {
            return $next($request, $response);
        }

        if ($selected = $build->environment()) {
            $request = $request->withAttribute(self::SELECTED_ENVIRONMENT_ATTRIBUTE, $selected);
            return $next($request, $response);
        }

        if ($selected = $this->getDeploymentEnvironment($build, $request)) {
            $request = $request->withAttribute(self::SELECTED_ENVIRONMENT_ATTRIBUTE, $selected);
            return $next($request, $response);
        }

        $environments = $this->getBuildableEnvironments($build->application());

        return $this->withTemplate($request, $response, $this->template, [
            'build' => $build,
            'environments' => $environments
        ]);
    }

    /**
     * @param Application $application
     *
     * @return array
     */
    private function getBuildableEnvironments(Application $application)
    {
        $environments = $this->environmentRepository->getBuildableEnvironmentsByApplication($application);

        return $environments;
    }

    /**
     * This will try several methods to grab the push environment.
     *
     * This will grab the environment from the build if it exists, or use the environment repository to search for
     * an environment using the provided request argument.
     *
     * @param Build $build
     * @param ServerRequestInterface $request
     *
     * @return Environment|null
     */
    private function getDeploymentEnvironment(Build $build, ServerRequestInterface $request)
    {
        if ($build->environment()) {
            return $build->environment();
        }

        $targetID = $request->getQueryParams()['target'] ?? null;
        $environmentID = $request->getQueryParams()['environment'] ?? null;

        if ($targetID && $target = $this->targetRepository->find($targetID)) {
            return $target->group()->environment();
        }

        if ($environmentID && $environment = $this->environmentRepository->find($environmentID)) {
            return $environment;
        }

        return null;
    }
}
