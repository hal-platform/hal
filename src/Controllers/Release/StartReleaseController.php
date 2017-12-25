<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Release;

use Doctrine\ORM\EntityManagerInterface;
use Hal\Core\Entity\Build;
use Hal\Core\Entity\Release;
use Hal\Core\Entity\Target;
use Hal\Core\Entity\Environment;
use Hal\Core\Repository\TargetRepository;
use Hal\UI\Controllers\RedirectableControllerTrait;
use Hal\UI\Controllers\SessionTrait;
use Hal\UI\Controllers\TemplatedControllerTrait;
use Hal\UI\Flash;
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
     * @var TargetRepository
     */
    private $targetRepository;

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
        $this->targetRepository = $em->getRepository(Target::class);
        $this->uri = $uri;
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $build = $request->getAttribute(Build::class);
        $environment = $this->getDeploymentEnvironment($request);

        if (!$build->isSuccess() || !$environment) {
            $this
                ->getFlash($request)
                ->withMessage(Flash::ERROR, self::ERR_NOT_BUILDABLE);

            return $this->withRedirectRoute($response, $this->uri, 'build', ['build' => $build->id()]);
        }

        $statuses = [];

        $targets = $this->targetRepository->getByApplicationAndEnvironment($build->application(), $environment);
        foreach ($targets as $target) {
            $statuses[] = [
                'target' => $target,
                'release' => $target->release()
            ];
        }

        return $this->withTemplate($request, $response, $this->template, [
            'build' => $build,
            'selected_environment' => $environment,
            'selected' => $request->getQueryParams()['target'] ?? '',
            'statuses' => $statuses
        ]);
    }

    /**
     * The selected environment should have been populated by the previous middleware.
     *
     * @param ServerRequestInterface $request
     *
     * @return Environment
     */
    private function getDeploymentEnvironment(ServerRequestInterface $request)
    {
        return $request->getAttribute(SelectEnvironmentMiddleware::SELECTED_ENVIRONMENT_ATTRIBUTE);
    }
}
