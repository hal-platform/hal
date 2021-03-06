<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\API\Release;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Hal\Core\Entity\Environment;
use Hal\Core\Entity\JobType\Build;
use Hal\Core\Entity\Target;
use Hal\UI\API\HypermediaResource;
use Hal\UI\API\ResponseFormatter;
use Hal\UI\Controllers\APITrait;
use Hal\UI\Controllers\SessionTrait;
use Hal\UI\Validator\ReleaseValidator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Panthor\ControllerInterface;
use QL\Panthor\HTTPProblem\HTTPProblem;
use QL\Panthor\HTTPProblem\ProblemRendererInterface;

/**
 * Permission checking is handled by ReleaseValidator
 */
class DeployController implements ControllerInterface
{
    use APITrait;
    use SessionTrait;

    private const ERR_CHECK_FORM = 'Cannot deploy release due to form submission failure. Please check errors.';
    private const ERR_INVALID_DEPLOY = 'Cannot start deployment to the provided targets.';

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var EntityRepository
     */
    private $targetRepository;

    /**
     * @var ReleaseValidator
     */
    private $releaseValidator;

    /**
     * @var ResponseFormatter
     */
    private $formatter;

    /**
     * @var ProblemRendererInterface
     */
    private $problemRenderer;

    /**
     * @param EntityManagerInterface $em
     * @param ReleaseValidator $pushValidator
     * @param ResponseFormatter $formatter
     * @param ProblemRendererInterface $problemRenderer
     */
    public function __construct(
        EntityManagerInterface $em,
        ReleaseValidator $pushValidator,
        ResponseFormatter $formatter,
        ProblemRendererInterface $problemRenderer
    ) {
        $this->em = $em;
        $this->targetRepository = $this->em->getRepository(Target::class);
        $this->releaseValidator = $pushValidator;

        $this->formatter = $formatter;
        $this->problemRenderer = $problemRenderer;
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $build = $request->getAttribute(Build::class);
        $user = $this->getUser($request);

        $targets = $request->getParsedBody()['targets'] ?? [];

        if (!$targets) {
            $problem = new HTTPProblem(400, self::ERR_CHECK_FORM, ['errors' => [self::ERR_INVALID_DEPLOY]]);
            return $this->renderProblem($response, $this->problemRenderer, $problem);
        }

        $environment = $this->getReleaseEnvironment($targets);
        $releases = $this->releaseValidator->isValid($build->application(), $user, $environment, $build, $targets);

        if (!$releases) {
            $problem = new HTTPProblem(400, self::ERR_CHECK_FORM, ['errors' => $this->releaseValidator->errors()]);
            return $this->renderProblem($response, $this->problemRenderer, $problem);
        }

        foreach ($releases as $release) {
            // record releases as active job on each target
            $target = $release->target();
            $target->withLastJob($release);

            $this->em->persist($target);
            $this->em->persist($release);
        }

        $this->em->flush();

        $data = [
            'count' => count($releases),
        ];

        $resource = new HypermediaResource($data, [], [
            'build' => $build,
            'releases' => $releases,
        ]);

        $resource->withEmbedded(['build', 'releases']);

        $body = $this->formatter->buildHypermediaResponse($request, $resource);

        return $this->withHypermediaEndpoint($request, $response, $body, 201);
    }

    /**
     * @param array $targets
     *
     * @return Environment|null
     */
    private function getReleaseEnvironment(array $targets)
    {
        if (!$targets) {
            return null;
        }

        $target = array_shift($targets);

        $target = $this->targetRepository->find($target);
        if (!$target instanceof Target) {
            return null;
        }

        return $target->environment();
    }
}
