<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Application;

use Doctrine\ORM\EntityManagerInterface;
use Hal\UI\Controllers\RedirectableControllerTrait;
use Hal\UI\Controllers\SessionTrait;
use Hal\UI\Flash;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Hal\Core\Entity\Build;
use QL\Hal\Core\Entity\Deployment;
use QL\Hal\Core\Entity\Push;
use QL\Hal\Core\Entity\Application;
use QL\Panthor\ControllerInterface;
use QL\Panthor\Utility\URI;

class RemoveApplicationController implements ControllerInterface
{
    use RedirectableControllerTrait;
    use SessionTrait;

    const MSG_SUCCESS = 'Application "%s" removed.';
    const ERR_HAS_DEPLOYMENTS = 'Cannot remove application. All server deployments must first be removed.';

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var URI
     */
    private $uri;

    /**
     * @param URI $uri
     */
    public function __construct(EntityManagerInterface $em, URI $uri)
    {
        $this->em = $em;
        $this->uri = $uri;
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $application = $request->getAttribute(Application::class);

        if ($this->doesApplicationHaveChildren($application)) {
            $this->withFlash($request, Flash::ERROR, self::ERR_HAS_DEPLOYMENTS);
            return $this->withRedirectRoute($response, $this->uri, 'application', ['application' => $application->id()]);
        }

        $this->em->remove($application);
        $this->em->flush();

        $message = sprintf(self::MSG_SUCCESS, $application->key());

        $this->withFlash($request, Flash::SUCCESS, $message);
        return $this->withRedirectRoute($response, $this->uri, 'applications');
    }

    /**
     * @param Application $application
     *
     * @return bool
     */
    private function doesApplicationHaveChildren(Application $application)
    {
        $targets = $this->em
            ->getRepository(Deployment::class)
            ->findOneBy(['application' => $application]);

        if (count($targets) > 0) return true;

        $builds = $this->em
            ->getRepository(Build::class)
            ->findOneBy(['application' => $application]);

        if (count($builds) > 0) return true;

        $deployments = $this->em
            ->getRepository(Push::class)
            ->findOneBy(['application' => $application]);

        if (count($deployments) > 0) return true;

        return false;
    }
}
