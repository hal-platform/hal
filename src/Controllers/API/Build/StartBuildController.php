<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\API\Build;

use Doctrine\ORM\EntityManagerInterface;
use Hal\UI\Controllers\APITrait;
use Hal\UI\Controllers\SessionTrait;
use Hal\UI\API\ResponseFormatter;
use Hal\UI\Validator\BuildValidator;
use Hal\UI\Validator\PushValidator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Hal\Core\Entity\Application;
use QL\Panthor\ControllerInterface;
use QL\Panthor\HTTPProblem\HTTPProblem;
use QL\Panthor\HTTPProblem\ProblemRendererInterface;

/**
 * Permission checking is handled by BuildValidator
 */
class StartBuildController implements ControllerInterface
{
    use APITrait;
    use SessionTrait;

    private const ERR_CHECK_FORM = 'Cannot start build due to form submission failure. Please check errors.';
    private const ERR_INVALID_DEPLOY = 'Cannot create child processes for selected targets.';

    /**
     * @var ResponseFormatter
     */
    private $formatter;

    /**
     * @var BuildValidator
     */
    private $buildValidator;

    /**
     * @var PushValidator
     */
    private $pushValidator;

    /**
     * @var ProblemRendererInterface
     */
    private $problemRenderer;

    /**
     * @param ResponseFormatter $formatter
     * @param BuildValidator $buildValidator
     * @param PushValidator $pushValidator
     *
     * @param EntityManagerInterface $em
     * @param ProblemRendererInterface $problemRenderer
     */
    public function __construct(
        ResponseFormatter $formatter,
        BuildValidator $buildValidator,
        PushValidator $pushValidator,

        EntityManagerInterface $em,
        ProblemRendererInterface $problemRenderer
    ) {
        $this->formatter = $formatter;
        $this->buildValidator = $buildValidator;
        $this->pushValidator = $pushValidator;

        $this->em = $em;
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
        $deployments = $request->getParsedBody()['deployments'] ?? [];

        $build = $this->buildValidator->isValid($application, $user, $environmentID, $reference, '');

        if (!$build) {
            $problem = new HTTPProblem(400, self::ERR_CHECK_FORM, ['errors' => $this->buildValidator->errors()]);
            return $this->renderProblem($response, $this->problemRenderer, $problem);
        }

        $children = null;
        if ($deployments && is_array($deployments)) {
            $children = $this->pushValidator->isProcessValid($application, $user, $build->environment(), $build, $deployments);
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

        $data = $this->formatter->buildResponse($request, $build);
        return $this->withHypermediaEndpoint($request, $response, $data, 201);
    }
}
