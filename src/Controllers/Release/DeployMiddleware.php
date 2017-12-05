<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Release;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Hal\Core\Entity\Build;
use Hal\Core\Entity\Release;
use Hal\Core\Entity\Target;
use Hal\Core\Entity\Environment;
use Hal\UI\Controllers\RedirectableControllerTrait;
use Hal\UI\Controllers\SessionTrait;
use Hal\UI\Controllers\TemplatedControllerTrait;
use Hal\UI\Flash;
use Hal\UI\Service\StickyEnvironmentService;
use Hal\UI\Validator\ReleaseValidator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Panthor\MiddlewareInterface;
use QL\Panthor\Utility\URI;

class DeployMiddleware implements MiddlewareInterface
{
    use RedirectableControllerTrait;
    use SessionTrait;
    use TemplatedControllerTrait;

    const WAIT_FOR_IT = "The release has been queued and will be deployed shortly.";

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var ReleaseValidator
     */
    private $validator;

    /**
     * @var EntityRepository
     */
    private $environmentRepository;
    private $targetRepository;

    /**
     * @var StickyEnvironmentService
     */
    private $stickyService;

    /**
     * @var URI
     */
    private $uri;

    /**
     * @param EntityManagerInterface $em
     * @param ReleaseValidator $validator
     * @param StickyEnvironmentService $stickyService
     * @param URI $uri
     */
    public function __construct(
        EntityManagerInterface $em,
        ReleaseValidator $validator,
        StickyEnvironmentService $stickyService,
        URI $uri
    ) {
        $this->environmentRepository = $em->getRepository(Environment::class);
        $this->targetRepository = $em->getRepository(Target::class);
        $this->em = $em;

        $this->validator = $validator;
        $this->stickyService = $stickyService;
        $this->uri = $uri;
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        $build = $request->getAttribute(Build::class);
        $environment = $this->getDeploymentEnvironment($request);

        if ($request->getMethod() !== 'POST' || !$build->isSuccess() || !$environment) {
            return $next($request, $response);
        }

        $user = $this->getUser($request);
        $targets = $request->getParsedBody()['targets'] ?? [];
        $application = $build->application();

        // passed separately, in case one day we support cross-env builds?
        $releases = $this->validator->isValid($application, $user, $environment, $build, $targets);

        // Pass through to controller if errors
        if (!$releases) {
            return $next(
                $this->withContext($request, ['errors' => $this->validator->errors()]),
                $response
            );
        }

        // commit pushes
        foreach ($releases as $release) {
            // record pushes as active push on each deployment
            $target = $release->target();
            $target->withRelease($release);

            $this->em->persist($target);
            $this->em->persist($release);
        }

        $this->em->flush();

        // override sticky environment
        $response = $this->stickyService->save($request, $response, $application->id(), $environment->id());

        // flash and redirect
        $this
            ->getFlash($request)
            ->withMessage(Flash::SUCCESS, self::WAIT_FOR_IT);

        return $this->withRedirectRoute($response, $this->uri, 'application.dashboard', ['application' => $application->id()]);
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
