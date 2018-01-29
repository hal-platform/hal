<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Build;

use Doctrine\ORM\EntityManagerInterface;
use Hal\UI\Controllers\CSRFTrait;
use Hal\UI\Controllers\RedirectableControllerTrait;
use Hal\UI\Controllers\SessionTrait;
use Hal\UI\Controllers\TemplatedControllerTrait;
use Hal\UI\Service\StickyEnvironmentService;
use Hal\UI\Validator\BuildValidator;
use Hal\UI\Validator\MetaValidator;
use Hal\UI\Validator\ReleaseValidator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Hal\Core\Entity\Application;
use Hal\Core\Entity\User;
use Hal\Core\Entity\JobType\Build;
use QL\Panthor\MiddlewareInterface;
use QL\Panthor\Utility\URI;

/**
 * Permission checking is handled by BuildStartValidator
 */
class StartBuildMiddleware implements MiddlewareInterface
{
    use CSRFTrait;
    use RedirectableControllerTrait;
    use SessionTrait;
    use TemplatedControllerTrait;

    const WAIT_FOR_IT = 'The build has been queued.';

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var BuildValidator
     */
    private $validator;

    /**
     * @var ReleaseValidator
     */
    private $releaseValidator;

    /**
     * @var MetaValidator
     */
    private $metaValidator;

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
     * @param BuildValidator $validator
     * @param ReleaseValidator $releaseValidator
     * @param MetaValidator $metaValidator
     * @param StickyEnvironmentService $stickyService
     * @param URI $uri
     */
    public function __construct(
        EntityManagerInterface $em,
        BuildValidator $validator,
        ReleaseValidator $releaseValidator,
        MetaValidator $metaValidator,
        StickyEnvironmentService $stickyService,
        URI $uri
    ) {
        $this->em = $em;
        $this->validator = $validator;
        $this->releaseValidator = $releaseValidator;
        $this->metaValidator = $metaValidator;
        $this->stickyService = $stickyService;
        $this->uri = $uri;
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        $application = $request->getAttribute(Application::class);
        $user = $this->getUser($request);

        if ($request->getMethod() !== 'POST') {
            return $next($request, $response);
        }

        if (!$this->isCSRFValid($request)) {
            return $next($request, $response);
        }

        $data = $request->getParsedBody();

        $env = $data['environment'] ?? '';
        $ref = $data['reference'] ?? '';
        $search = $data['search'] ?? '';

        if ($env === '!any') {
            $env = null;
        }

        $build = $this->validator->isValid($application, $user, $env, $ref, $search);

        // if validator didn't create a build, add errors and pass through to controller
        if (!$build) {
            return $next($this->withContext($request, ['errors' => $this->validator->errors()]), $response);
        }

        if (!$this->saveMetadata($build, $request)) {
            return $next($this->withContext($request, ['errors' => $this->metaValidator->errors()]), $response);
        }

        $targets = $data['targets'] ?? [];
        if (!$this->saveScheduledTargets($build, $user, $targets)) {
            return $next($this->withContext($request, ['errors' => $this->releaseValidator->errors()]), $response);
        }

        $this->em->persist($build);
        $this->em->flush();

        // override sticky environment
        if ($env) {
            $response = $this->stickyService->save($request, $response, $application->id(), $env);
        }

        // flash and redirect
        $this->withFlashSuccess($request, self::WAIT_FOR_IT);
        return $this->withRedirectRoute($response, $this->uri, 'build', ['build' => $build->id()]);
    }

    /**
     * @param Build $build
     * @param ServerRequestInterface $request
     *
     * @return bool
     */
    private function saveMetadata(Build $build, ServerRequestInterface $request)
    {
        $names = $request->getParsedBody()['metadata_names'] ?? [];
        $values = $request->getParsedBody()['metadata_values'] ?? [];

        $names = is_array($names) ? $names : [];
        $values = is_array($values) ? $values : [];

        if (!$names && !$values) {
            return true;
        }

        $metadatas = [];
        foreach ($names as $index => $name) {
            if ($name && isset($values[$index]) && strlen($values[$index]) > 0) {
                $metadatas[$name] = $values[$index];
            }
        }

        $metas = $this->metaValidator->isBulkValid($build, $metadatas);
        if (!$metas) {
            return false;
        }

        foreach ($metas as $meta) {
            $this->em->persist($meta);
        }

        return true;
    }

    /**
     * @param Build $build
     * @param User $user
     * @param array $targets
     *
     * @return bool
     */
    private function saveScheduledTargets(Build $build, User $user, array $targets)
    {
        if (!$build->environment()) {
            return true;
        }

        $scheduled = $this->maybeScheduleActions($build, $user, $targets);

        if ($targets && !$scheduled) {
            return false;
        }

        if ($scheduled) {
            foreach ($scheduled as $action) {
                $this->em->persist($action);
            }
        }

        return true;
    }

    /**
     * @param Build $build
     * @param User $user
     * @param array $targets
     *
     * @return array|null
     */
    private function maybeScheduleActions(Build $build, User $user, array $targets)
    {
        if (!$targets) {
            return null;
        }

        return $this->releaseValidator->isScheduledJobValid(
            $build->application(),
            $user,
            $build->environment(),
            $build,
            $targets
        );
    }
}
