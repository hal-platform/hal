<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\API\Release;

use Doctrine\ORM\EntityManagerInterface;
use Hal\Core\Entity\Build;
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

    /**
     * @var EntityManagerInterface
     */
    private $em;

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

        $releases = $this->releaseValidator->isValid($build->application(), $user, $build->environment(), $build, $targets);

        if (!$releases) {
            $problem = new HTTPProblem(400, self::ERR_CHECK_FORM, ['errors' => $this->releaseValidator->errors()]);

            return $this->renderProblem($response, $this->problemRenderer, $problem);
        }

        foreach ($releases as $release) {
            $this->em->persist($release);
        }

        $this->em->flush();

        $data = [
            'count' => count($releases)
        ];

        $resource = new HypermediaResource($data, [], [
            'build' => $build,
            'releases' => $releases
        ]);

        $resource->withEmbedded(['releases']);

        $body = $this->formatter->buildHypermediaResponse($request, $resource);

        return $this->withHypermediaEndpoint($request, $response, $body, 201);
    }
}
