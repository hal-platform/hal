<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Application;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Hal\Core\Entity\Application;
use Hal\Core\Entity\Target;
use Hal\Core\Entity\User\UserPermission;
use Hal\UI\Controllers\CSRFTrait;
use Hal\UI\Controllers\RedirectableControllerTrait;
use Hal\UI\Controllers\SessionTrait;
use Hal\UI\Security\AuthorizationService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Panthor\ControllerInterface;
use QL\Panthor\Utility\URI;

class RemoveApplicationHandler implements ControllerInterface
{
    use CSRFTrait;
    use RedirectableControllerTrait;
    use SessionTrait;

    const MSG_SUCCESS = '"%s" application removed.';

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var EntityRepository
     */
    private $targetRepo;
    private $permissionRepo;

    /**
     * @var AuthorizationService
     */
    private $authorizationService;

    /**
     * @var URI
     */
    private $uri;

    /**
     * @param EntityManagerInterface $em
     * @param AuthorizationService $authorizationService
     * @param URI $uri
     */
    public function __construct(EntityManagerInterface $em, AuthorizationService $authorizationService, URI $uri)
    {
        $this->em = $em;
        $this->targetRepo = $em->getRepository(Target::class);
        $this->permissionRepo = $em->getRepository(UserPermission::class);

        $this->authorizationService = $authorizationService;
        $this->uri = $uri;
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $application = $request->getAttribute(Application::class);

        if (!$this->isCSRFValid($request)) {
            $this->withFlashError($request, $this->CSRFError());
            return $this->withRedirectRoute($response, $this->uri, 'application', ['application' => $application->id()]);
        }

        // Remove targets and permissions first
        $this->removeTargets($application);
        $this->removePermissions($application);

        $this->em->remove($application);
        $this->em->flush();

        $msg = sprintf(self::MSG_SUCCESS, $application->name());

        $this->withFlashSuccess($request, $msg);
        return $this->withRedirectRoute($response, $this->uri, 'applications');
    }

    /**
     * @param Application $application
     *
     * @return void
     */
    private function removeTargets(Application $application)
    {
        $targets = $this->targetRepo->findBy(['application' => $application]);
        foreach ($targets as $target) {
            $this->em->remove($target);
        }
    }

    /**
     * @param Application $application
     *
     * @return void
     */
    private function removePermissions(Application $application)
    {
        $permission = $this->permissionRepo->findBy(['application' => $application]);
        foreach ($permission as $permission) {
            $this->authorizationService->removeUserPermissions($permission, true);
        }
    }
}
