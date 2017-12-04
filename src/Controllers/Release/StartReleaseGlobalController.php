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
use Hal\UI\Controllers\RedirectableControllerTrait;
use Hal\UI\Controllers\SessionTrait;
use Hal\UI\Controllers\TemplatedControllerTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use QL\Panthor\Utility\URI;

class StartReleaseGlobalController implements ControllerInterface
{
    use RedirectableControllerTrait;
    use TemplatedControllerTrait;

    /**
     * @var TemplateInterface
     */
    private $template;

    /**
     * @var EntityRepository
     */
    private $environmentRepository;

    /**
     * @var URI
     */
    private $uri;

    /**
     * @var callable
     */
    private $notFound;

    /**
     * @param TemplateInterface $template
     * @param EntityManagerInterface $em
     * @param URI $uri
     */
    public function __construct(
        TemplateInterface $template,
        EntityManagerInterface $em,
        URI $uri,
        callable $notFound
    ) {
        $this->template = $template;
        $this->environmentRepository = $em->getRepository(Environment::class);

        $this->notFound = $notFound;
        $this->uri = $uri;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $build = $request->getAttribute(Build::class);

        if (!$build || !$build->isSuccess()) {
            return ($this->notFound)($request, $response);
        }

        if ($build->environment()) {
            return $this->withRedirectRoute($response, $this->uri, 'release.start', ['build' => $build->id()]);
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
     * @return Environment[]
     */
    private function getBuildableEnvironments(Application $application)
    {
        $environments = $this->environmentRepository->getBuildableEnvironmentsByApplication($application);

        return $environments;
    }
}
