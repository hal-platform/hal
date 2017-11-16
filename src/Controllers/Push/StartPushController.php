<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Push;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Hal\Core\Entity\Build;
use Hal\Core\Entity\Release;
use Hal\Core\Entity\Target;
use Hal\Core\Repository\ReleaseRepository;
use Hal\UI\Controllers\RedirectableControllerTrait;
use Hal\UI\Controllers\SessionTrait;
use Hal\UI\Controllers\TemplatedControllerTrait;
use Hal\UI\Flash;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use QL\Panthor\Utility\URI;

class StartPushController implements ControllerInterface
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
     * @var EntityRepository
     */
    private $buildRepository;
    private $targetRepository;

    /**
     * @var ReleaseRepository
     */
    private $releaseRepository;

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

        $this->buildRepository = $em->getRepository(Build::class);
        $this->releaseRepository = $em->getRepository(Release::class);
        $this->targetRepository = $em->getRepository(Target::class);

        $this->uri = $uri;
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $build = $request->getAttribute(Build::class);

        if (!$build->isSuccess()) {
            $this
                ->getFlash($request)
                ->withMessage(Flash::ERROR, self::ERR_NOT_BUILDABLE);

            return $this->withRedirectRoute($response, $this->uri, 'build', ['build' => $build->id()]);
        }

        $targets = $this->targetRepository->getByApplicationAndEnvironment($build->application(), $build->environment());
        $statuses = [];
        foreach ($targets as $target) {
            $release = $this->releaseRepository->getByTarget($target, 1)->getIterator()->current();

            $statuses[] = ['target' => $target, 'release' => $release];
        }

        return $this->withTemplate($request, $response, $this->template, [
            'build' => $build,
            'selected' => $request->getQueryParams()['target'] ?? '',
            'statuses' => $statuses
        ]);
    }
}
