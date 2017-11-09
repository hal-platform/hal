<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Target;

use Doctrine\ORM\EntityManagerInterface;
use Hal\UI\Controllers\RedirectableControllerTrait;
use Hal\UI\Controllers\SessionTrait;
use Hal\UI\Flash;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Hal\Core\Entity\Application;
use Hal\Core\Entity\Target;
use Hal\Core\Entity\Environment;
use QL\Panthor\ControllerInterface;
use QL\Panthor\Utility\URI;

class RemoveTargetController implements ControllerInterface
{
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
     * @param EntityManagerInterface $em
     * @param URI $uri
     */
    public function __construct(EntityManagerInterface $em, URI $uri)
    {
        $this->em = $em;
        $this->uri = $uri;
    }

    /**
     * @inheritdoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $application = $request->getAttribute(Application::class);
        $target = $request->getAttribute(Target::class);

        $this->em->remove($target);
        $this->em->flush();

        // Clear cached query for buildable environments
        $envRepo = $this->em
            ->getRepository(Environment::class)
            ->clearBuildableEnvironmentsByApplication($application);

        $this->withFlash($request, Flash::SUCCESS, self::MSG_SUCCESS);
        return $this->withRedirectRoute($response, $this->uri, 'targets', ['application' => $application->id()]);
    }
}
