<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Target;

use Doctrine\ORM\EntityManagerInterface;
use Hal\Core\Entity\Application;
use Hal\Core\Entity\Environment;
use Hal\Core\Entity\Target;
use Hal\Core\Repository\EnvironmentRepository;
use Hal\UI\Controllers\CSRFTrait;
use Hal\UI\Controllers\RedirectableControllerTrait;
use Hal\UI\Controllers\SessionTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Panthor\ControllerInterface;
use QL\Panthor\Utility\URI;

class RemoveTargetHandler implements ControllerInterface
{
    use CSRFTrait;
    use RedirectableControllerTrait;
    use SessionTrait;

    private const MSG_SUCCESS = 'Target target removed.';

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var URI
     */
    private $uri;

    /**
     * @var EnvironmentRepository
     */
    private $environmentRepository;

    /**
     * @param EntityManagerInterface $em
     * @param URI $uri
     */
    public function __construct(EntityManagerInterface $em, URI $uri)
    {
        $this->em = $em;
        $this->environmentRepository = $this->em->getRepository(Environment::class);

        $this->uri = $uri;
    }

    /**
     * @inheritdoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $application = $request->getAttribute(Application::class);
        $target = $request->getAttribute(Target::class);

        if (!$this->isCSRFValid($request)) {
            $this->withFlashError($request, $this->CSRFError());
            return $this->withRedirectRoute($response, $this->uri, 'target', ['application' => $application->id(), 'target' => $target->id()]);
        }

        $this->em->remove($target);
        $this->em->flush();

        // Clear cached query for buildable environments
        $this->environmentRepository->clearBuildableEnvironmentsByApplication($application);

        $this->withFlashSuccess($request, self::MSG_SUCCESS);
        return $this->withRedirectRoute($response, $this->uri, 'targets', ['application' => $application->id()]);
    }
}
