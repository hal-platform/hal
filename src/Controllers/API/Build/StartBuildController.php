<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\API\Build;

use Doctrine\ORM\EntityManagerInterface;
use Hal\Core\Entity\Application;
use Hal\UI\API\Normalizer\BuildNormalizer;
use Hal\UI\API\ResponseFormatter;
use Hal\UI\Controllers\APITrait;
use Hal\UI\Controllers\SessionTrait;
use Hal\UI\Validator\BuildValidator;
use Hal\UI\Validator\ReleaseValidator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Panthor\ControllerInterface;
use QL\Panthor\HTTPProblem\HTTPProblem;
use QL\Panthor\HTTPProblem\ProblemRendererInterface;

/**
 * Permission checking is handled by BuildValidator and ReleaseValidator
 */
class StartBuildController implements ControllerInterface
{
    use APITrait;
    use SessionTrait;

    private const ERR_CHECK_FORM = 'Cannot start build due to form submission failure. Please check errors.';
    private const ERR_INVALID_DEPLOY = 'Cannot create child processes for selected targets.';
    private const ERR_GLOBAL_DEPLOY = 'Cannot autodeploy global build.';

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var BuildValidator
     */
    private $buildValidator;

    /**
     * @var ReleaseValidator
     */
    private $pushValidator;

    /**
     * @var ResponseFormatter
     */
    private $formatter;

    /**
     * @var BuildNormalizer
     */
    private $normalizer;

    /**
     * @var ProblemRendererInterface
     */
    private $problemRenderer;

    /**
     * @param EntityManagerInterface $em
     * @param BuildValidator $buildValidator
     * @param ReleaseValidator $pushValidator
     * @param ResponseFormatter $formatter
     * @param BuildNormalizer $normalizer
     * @param ProblemRendererInterface $problemRenderer
     */
    public function __construct(
        EntityManagerInterface $em,
        BuildValidator $buildValidator,
        ReleaseValidator $pushValidator,
        ResponseFormatter $formatter,
        BuildNormalizer $normalizer,
        ProblemRendererInterface $problemRenderer
    ) {
        $this->em = $em;
        $this->buildValidator = $buildValidator;
        $this->pushValidator = $pushValidator;

        $this->formatter = $formatter;
        $this->normalizer = $normalizer;
        $this->problemRenderer = $problemRenderer;
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $application = $request->getAttribute(Application::class);
        $user = $this->getUser($request);

        $environmentID = $request->getParsedBody()['environment'] ?? '';
        $reference = $request->getParsedBody()['reference'] ?? '';
        $targets = $request->getParsedBody()['targets'] ?? [];

        $build = $this->buildValidator->isValid($application, $user, $environmentID, $reference, '');

        if (!$build) {
            $problem = new HTTPProblem(400, self::ERR_CHECK_FORM, ['errors' => $this->buildValidator->errors()]);

            return $this->renderProblem($response, $this->problemRenderer, $problem);
        }

        $children = null;
        if ($targets && is_array($targets)) {
            if (!$build->environment()) {
                throw new HTTPProblem(400, self::ERR_GLOBAL_DEPLOY);
            }
            $children = $this->pushValidator->isProcessValid($application, $user, $build->environment(), $build, $targets);
            if (!$children) {
                $problem = new HTTPProblem(400, self::ERR_INVALID_DEPLOY, ['errors' => $this->pushValidator->errors()]);

                return $this->renderProblem($response, $this->problemRenderer, $problem);
            }
        }

        // persist to database
        if ($children) {
            foreach ($children as $process) {
                $this->em->persist($process);
            }
        }

        $this->em->persist($build);
        $this->em->flush();

        $resource = $this->normalizer->resource($build, ['application']);
        $body = $this->formatter->buildHypermediaResponse($request, $resource);

        return $this->withHypermediaEndpoint($request, $response, $body, 201);
    }
}
