<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Build;

use Doctrine\ORM\EntityManagerInterface;
use Hal\UI\Controllers\RedirectableControllerTrait;
use Hal\UI\Controllers\SessionTrait;
use Hal\UI\Controllers\TemplatedControllerTrait;
use Hal\UI\Flash;
use Hal\UI\Service\StickyEnvironmentService;
use Hal\UI\Validator\BuildValidator;
use Hal\UI\Validator\PushValidator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Hal\Core\Entity\Application;
use QL\Hal\Core\Entity\Build;
use QL\Hal\Core\Entity\User;
use QL\Panthor\MiddlewareInterface;
use QL\Panthor\Utility\URI;

/**
 * Permission checking is handled by BuildStartValidator
 */
class StartBuildHandler implements MiddlewareInterface
{
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
     * @var PushValidator
     */
    private $pushValidator;

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
     * @param PushValidator $pushValidator
     * @param StickyEnvironmentService $stickyService
     * @param URI $uri
     */
    public function __construct(
        EntityManagerInterface $em,
        BuildValidator $validator,
        PushValidator $pushValidator,
        StickyEnvironmentService $stickyService,
        URI $uri
    ) {
        $this->em = $em;
        $this->validator = $validator;
        $this->pushValidator = $pushValidator;
        $this->stickyService = $stickyService;
        $this->uri = $uri;
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        if ($request->getMethod() !== 'POST') {
            return $next($request, $response);
        }

        $application = $request->getAttribute(Application::class);
        $user = $this->getUser($request);

        $env = $request->getParsedBody()['environment'] ?? '';
        $ref = $request->getParsedBody()['reference'] ?? '';
        $search = $request->getParsedBody()['search'] ?? '';

        $build = $this->validator->isValid($application, $user, $env, $ref, $search);

        // if validator didn't create a build, add errors and pass through to controller
        if (!$build) {
            return $next(
                $this->withContext($request, ['errors' => $this->validator->errors()]),
                $response
            );
        }

        $deployments = $request->getParsedBody()['deployments'] ?? [];
        $children = $this->maybeMakeChildren($build, $user, $deployments);
        if ($deployments && !$children) {
            // child push validation failed, bomb out.
            return $next(
                $this->withContext($request, ['errors' => $this->pushValidator->errors()]),
                $response
            );
        }

        // persist to database
        if ($children) {
            foreach ($children as $process) {
                $this->em->persist($process);
            }
        }

        $this->em->persist($build);
        $this->em->flush();

        // override sticky environment
        $response = $this->stickyService->save($request, $response, $application->id(), $env);

        // flash and redirect
        $this
            ->getFlash($request)
            ->withMessage(Flash::SUCCESS, self::WAIT_FOR_IT);

        return $this->withRedirectRoute($response, $this->uri, 'build', ['build' => $build->id()]);
    }

    /**
     * @param Build $build
     * @param User $user
     * @param array|null $deployments
     *
     * @return array|null
     */
    private function maybeMakeChildren(Build $build, User $user, ?array $deployments)
    {
        if (!$deployments) {
            return null;
        }

        return $this->pushValidator->isProcessValid(
            $build->application(),
            $user,
            $build->environment(),
            $build,
            $deployments
        );
    }
}
